<?php namespace com\openai\unittest;

use com\openai\rest\Events;
use io\streams\{InputStream, MemoryInputStream};
use lang\IllegalStateException;
use test\{Assert, Test, Values};

class EventsTest {

  /** Returns input */
  private function input(array $lines): InputStream {
    return new MemoryInputStream(implode("\n", $lines));
  }

  /** Maps events to a list of pairs */
  private function pairsOf(iterable $events): array {
    $r= [];
    foreach ($events as $event => $value) {
      $r[]= [$event => $value];
    }
    return $r;
  }

  #[Test]
  public function can_create() {
    new Events($this->input([]));
  }

  #[Test]
  public function empty_input() {
    Assert::equals([], $this->pairsOf(new Events($this->input([]))));
  }

  #[Test]
  public function response_with_text_delta() {
    Assert::equals(
      [
        ['response.created' => [
          'type'          => 'response.created',
          'response'      => ['id' => 'test'],
        ]],
        ['response.output_item.added' => [
          'type'          => 'response.output_item.added',
          'output_index'  => 0,
          'item'          => ['type' => 'message'],
        ]],
        ['response.output_text.delta' => [
          'type'          => 'response.output_text.delta',
          'output_index'  => 0,
          'content_index' => 0,
          'delta'         => 'Test',
        ]],
        ['response.output_text.delta' => [
          'type'          => 'response.output_text.delta',
          'content_index' => 0,
          'output_index'  => 0,
          'delta'         => 'ed',
        ]],
        ['response.completed' => [
          'type'          => 'response.completed',
          'response'      => ['id' => 'test'],
        ]],
      ],
      $this->pairsOf(new Events($this->input([
        'event: response.created',
        'data: {"type":"response.created","response":{"id":"test"}}',
        '',
        'event: response.output_item.added',
        'data: {"type":"response.output_item.added","output_index":0,"item":{"type":"message"}}',
        '',
        'event: response.output_text.delta',
        'data: {"type":"response.output_text.delta","output_index":0,"content_index":0,"delta":"Test"}',
        '',
        'event: response.output_text.delta',
        'data: {"type":"response.output_text.delta","output_index":0,"content_index":0,"delta":"ed"}',
        '',
        'event: response.completed',
        'data: {"type":"response.completed","response":{"id":"test"}}'
      ])))
    );
  }
}