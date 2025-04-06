<?php namespace com\openai\unittest;

use io\streams\{Streams, MemoryInputStream};
use test\{Assert, Expect, Test};
use webservices\rest\{TestEndpoint, UnexpectedStatus};

abstract class ApiEndpointTest {

  /** @return com.openai.rest.ApiEndpoint */
  protected abstract function fixture(... $args);

  /** Returns a testing API endpoint */
  private function testingEndpoint(): TestEndpoint {
    return new TestEndpoint([
      'POST /audio/transcriptions' => function($call) {
        return $call->respond(200, 'OK', ['Content-Type' => 'application/json'], '"Test"');
      },
      'POST /responses' => function($call) {
        if ($call->request()->payload()->value()['stream'] ?? false) {
          $headers= ['Content-Type' => 'text/event-stream'];
          $payload= implode("\n", [
            'event: response.created',
            'data: {"response":{"id":"test"}}',
            '',
            'event: response.output_item.added',
            'data: {"type":"message"}',
            '',
            'event: response.output_text.delta',
            'data: {"delta":"Test"}',
            '',
            'event: response.completed',
            'data: {"response":{"id":"test"}}',
          ]);
        } else {
          $headers= ['Content-Type' => 'application/json'];
          $payload= '{"output":[{"type":"message","role":"assistant","content":[]}]}';
        }

        return $call->respond(200, 'OK', $headers, $payload);
      },
      'POST /chat/completions' => function($call) {
        if ($call->request()->payload()->value()['stream'] ?? false) {
          $headers= ['Content-Type' => 'text/event-stream'];
          $payload= implode("\n", [
            'data: {"choices":[{"delta":{"role":"assistant"}}]}',
            'data: {"choices":[{"delta":{"content":"Test"}}]}',
            'data: [DONE]',
          ]);
        } else {
          $headers= ['Content-Type' => 'application/json'];
          $payload= '{"choices":[{"message":{"role":"assistant","content":"Test"}}]}';
        }

        return $call->respond(200, 'OK', $headers, $payload);
      },
    ]);
  }

  #[Test]
  public function invoke() {
    $endpoint= $this->fixture($this->testingEndpoint());
    Assert::equals(
      ['output' => [['type' => 'message', 'role' => 'assistant', 'content' => []]]],
      $endpoint->api('/responses')->invoke(['stream' => false])
    );
  }

  #[Test]
  public function stream() {
    $endpoint= $this->fixture($this->testingEndpoint());
    Assert::equals(
      [
        'response.created'           => ['response' => ['id' => 'test']],
        'response.output_item.added' => ['type' => 'message'],
        'response.output_text.delta' => ['delta' => 'Test'],
        'response.completed'         => ['response' => ['id' => 'test']],
      ],
      iterator_to_array($endpoint->api('/responses')->stream(['stream' => true]))
    );
  }

  #[Test]
  public function invoke_completions() {
    $endpoint= $this->fixture($this->testingEndpoint());
    Assert::equals(
      ['choices' => [['message' => ['role' => 'assistant', 'content' => 'Test']]]],
      $endpoint->api('/chat/completions')->invoke(['stream' => false])
    );
  }

  #[Test]
  public function flow_completions() {
    $endpoint= $this->fixture($this->testingEndpoint());
    Assert::equals(
      ['choices' => [['message' => ['role' => 'assistant', 'content' => 'Test']]]],
      $endpoint->api('/chat/completions')->flow(['stream' => true])->result()
    );
  }

  #[Test]
  public function transmit() {
    $endpoint= $this->fixture($this->testingEndpoint());
    Assert::equals(
      '{"choices":[{"message":{"role":"assistant","content":"Test"}}]}',
      Streams::readAll($endpoint->api('/chat/completions')->transmit([])->stream())
    );
  }

  #[Test]
  public function upload() {
    $endpoint= $this->fixture($this->testingEndpoint());
    Assert::equals('Test', $endpoint->api('/audio/transcriptions')
      ->open(['model' => 'whisper-1'])
      ->transfer('file', new MemoryInputStream("\xf3\xff..."), 'test.mp3', 'audio/mp3')
      ->finish()
      ->value()
    );
  }

  #[Test, Expect(UnexpectedStatus::class)]
  public function invoke_non_existant_api() {
    $endpoint= $this->fixture($this->testingEndpoint());
    $endpoint->api('/non-exisant')->invoke([]);
  }
}