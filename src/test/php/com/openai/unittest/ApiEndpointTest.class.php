<?php namespace com\openai\unittest;

use io\streams\Streams;
use test\{Assert, Expect, Test};
use webservices\rest\{TestEndpoint, UnexpectedStatus};

abstract class ApiEndpointTest {

  /** @return com.openai.rest.ApiEndpoint */
  protected abstract function fixture(... $args);

  /** Returns a testing API endpoint */
  private function testingEndpoint(): TestEndpoint {
    return new TestEndpoint([
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
      }
    ]);
  }

  #[Test]
  public function invoke() {
    $endpoint= $this->fixture($this->testingEndpoint());
    Assert::equals(
      ['choices' => [['message' => ['role' => 'assistant', 'content' => 'Test']]]],
      $endpoint->api('/chat/completions')->invoke(['stream' => false])
    );
  }

  #[Test]
  public function stream() {
    $endpoint= $this->fixture($this->testingEndpoint());
    Assert::equals(
      ['choices' => [['message' => ['role' => 'assistant', 'content' => 'Test']]]],
      $endpoint->api('/chat/completions')->stream(['stream' => true])->result()
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

  #[Test, Expect(UnexpectedStatus::class)]
  public function invoke_non_existant_api() {
    $endpoint= $this->fixture($this->testingEndpoint());
    $endpoint->api('/non-exisant')->invoke([]);
  }
}