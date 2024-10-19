<?php namespace com\openai\unittest;

use com\openai\rest\OpenAIEndpoint;
use test\{Assert, Test};
use webservices\rest\TestEndpoint;

class OpenAIEndpointTest {
  const URI= 'https://sk-test@api.openai.example.com/v1';

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
  public function can_create() {
    new OpenAIEndpoint(self::URI);
  }

  #[Test]
  public function invoke() {
    $endpoint= new OpenAIEndpoint($this->testingEndpoint());
    Assert::equals(
      ['choices' => [['message' => ['role' => 'assistant', 'content' => 'Test']]]],
      $endpoint->api('/chat/completions')->invoke(['stream' => false])
    );
  }

  #[Test]
  public function stream() {
    $endpoint= new OpenAIEndpoint($this->testingEndpoint());
    Assert::equals(
      ['choices' => [['message' => ['role' => 'assistant', 'content' => 'Test']]]],
      $endpoint->api('/chat/completions')->stream(['stream' => true])->result()
    );
  }
}