<?php namespace com\openai\unittest;

use com\openai\rest\{Api, ApiEndpoint, Distributed, Distribution};
use lang\IllegalArgumentException;
use test\{Assert, Before, Expect, Test, Values};

class DistributedTest {
  use TestingEndpoint;

  private $strategy;

  /** Returns single and multiple endpoints */
  private function endpoints(): iterable {
    yield [[$this->testingEndpoint(1000)]];
    yield [[$this->testingEndpoint(1000), $this->testingEndpoint(null)]];
  }

  #[Before]
  public function strategy() {
    $this->strategy= new class() implements Distribution {
      public function distribute(array $endpoints): ApiEndpoint {
        return $endpoints[rand(0, sizeof($endpoints) - 1)];
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

    Assert::equals(999, $target->rateLimit()->remaining);
  }

  #[Test]
  public function rate_limit_reflects_sum_of_limits() {
    $endpoints= [$this->testingEndpoint(1000), $this->testingEndpoint(100)];

    Assert::equals(1100, (new Distributed($endpoints, $this->strategy))->rateLimit()->remaining);
  }
}