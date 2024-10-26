<?php namespace com\openai\unittest;

use com\openai\rest\{Api, ApiEndpoint, Distributed, Distribution, OpenAIEndpoint};
use lang\IllegalArgumentException;
use test\{Assert, Before, Expect, Test, Values};
use webservices\rest\TestEndpoint;

class DistributedTest {
  private $strategy;

  /** Returns a testing API endpoint */
  private function testingEndpoint(int $remaining= 0): OpenAIEndpoint {
    return new OpenAIEndpoint(new TestEndpoint([
      'POST /chat/completions' => function($call) use(&$remaining) {
        $remaining--;
        return $call->respond(
          200, 'OK',
          ['x-ratelimit-remaining-requests' => max(0, $remaining), 'Content-Type' => 'application/json'],
          '{"choices":[{"message":{"role":"assistant","content":"Test"}}]}'
        );
      }
    ]));
  }

  /** Returns single and multiple endpoints */
  private function endpoints(): iterable {
    yield [[$this->testingEndpoint()]];
    yield [[$this->testingEndpoint(), $this->testingEndpoint()]];
  }

  #[Before]
  public function strategy() {
    $this->strategy= new class() implements Distribution {
      public function distribute(array $endpoints): ApiEndpoint {
        return $endpoints[random_int(0, sizeof($endpoints) - 1)];
      }
    };
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function cannot_be_empty() {
    new Distributed([], $this->strategy);
  }

  #[Test, Values(from: 'endpoints')]
  public function can_create($endpoints) {
    new Distributed($endpoints, $this->strategy);
  }

  #[Test, Values(from: 'endpoints')]
  public function api_endpoint_returned($endpoints) {
    Assert::instance(Api::class, (new Distributed($endpoints, $this->strategy))->api('/embeddings'));
  }

  #[Test]
  public function rate_limit_updated() {
    $target= $this->testingEndpoint(1000);
    (new Distributed([$target], $this->strategy))->api('/chat/completions')->invoke(['prompt' => 'Test']);

    Assert::equals(999, $target->rateLimit->remaining);
  }
}