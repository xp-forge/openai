<?php namespace com\openai\unittest;

use com\openai\rest\EventStream;
use lang\IllegalStateException;
use test\{Assert, Test, Values};

class EventStreamTest {
  use Streams;

  /** Filtered deltas */
  private function filtered(): iterable {
    yield [null, [['role' => 'assistant'], ['content' => 'Test'], ['content' => 'ed']]];
    yield ['role', [['role' => 'assistant']]];
    yield ['content', [['content' => 'Test'], ['content' => 'ed']]];
  }

  #[Test]
  public function can_create() {
    new EventStream($this->input([]));
  }

  #[Test]
  public function receive_done_as_first_token() {
    $events= ['data: [DONE]'];
    Assert::equals([], $this->pairsOf((new EventStream($this->input($events)))->deltas()));
  }

  #[Test]
  public function does_not_continue_reading_after_done() {
    $events= ['data: [DONE]', '', 'data: "Test"'];
    Assert::equals([], $this->pairsOf((new EventStream($this->input($events)))->deltas()));
  }

  #[Test]
  public function deltas() {
    Assert::equals(
      [['role' => 'assistant'], ['content' => 'Test'], ['content' => 'ed']],
      $this->pairsOf((new EventStream($this->input($this->contentCompletions())))->deltas())
    );
  }

  #[Test]
  public function deltas_throws_if_already_consumed() {
    $events= new EventStream($this->input($this->contentCompletions()));
    iterator_count($events->deltas());

    Assert::throws(IllegalStateException::class, fn() => iterator_count($events->deltas()));
  }

  #[Test]
  public function ignores_newlines() {
    Assert::equals(
      [['role' => 'assistant'], ['content' => 'Test'], ['content' => 'ed']],
      $this->pairsOf((new EventStream($this->input(['', ...$this->contentCompletions()])))->deltas())
    );
  }

  #[Test, Values(from: 'filtered')]
  public function filtered_deltas($filter, $expected) {
    Assert::equals(
      $expected,
      $this->pairsOf((new EventStream($this->input($this->contentCompletions())))->deltas($filter))
    );
  }

  #[Test]
  public function result() {
    Assert::equals(
      ['choices' => [['message' => ['role' => 'assistant', 'content' => 'Tested']]]],
      (new EventStream($this->input($this->contentCompletions())))->result()
    );
  }

  #[Test]
  public function tool_call_deltas() {
    Assert::equals(
      [
        ['role' => 'assistant'],
        ['tool_calls' => [['type' => 'function', 'function' => ['name' => 'search', 'arguments' => '']]]],
        ['tool_calls' => [['function' => ['arguments' => '{']]]],
        ['tool_calls' => [['function' => ['arguments' => '}']]]],
      ],
      $this->pairsOf((new EventStream($this->input($this->toolCallCompletions())))->deltas())
    );
  }

  #[Test]
  public function tool_call_result() {
    $calls= [['type' => 'function', 'function' => ['name' => 'search', 'arguments' => '{}']]];
    Assert::equals(
      ['choices' => [[
        'message'       => ['role' => 'assistant', 'tool_calls' => $calls],
        'finish_reason' => 'function_call',
      ]]],
      (new EventStream($this->input($this->toolCallCompletions())))->result()
    );
  }
}