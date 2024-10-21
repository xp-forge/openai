<?php namespace com\openai\unittest;

use com\openai\rest\{Api, Distributed, OpenAIEndpoint};
use lang\IllegalArgumentException;
use test\{Assert, Before, Expect, Test};
use webservices\rest\TestEndpoint;

class DistributedTest {
  private $endpoints;

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

  #[Before]
  public function endpoints() {
    $this->endpoints= [
      new OpenAIEndpoint('https://sk-123@api.openai.example.com/v1'),
      new OpenAIEndpoint('https://sk-234@api.openai.example.com/v1'),
    ];
  }

  #[Test]
  public function can_create() {
    new Distributed($this->endpoints);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function cannot_be_empty() {
    new Distributed([]);
  }

  #[Test]
  public function distribute_to_one_of_the_given_endpoints() {
    $target= (new Distributed($this->endpoints))->distribute();
    Assert::true(in_array($target, $this->endpoints, true));
  }

  #[Test]
  public function api_endpoint_returned() {
    Assert::instance(Api::class, (new Distributed($this->endpoints))->api('/embeddings'));
  }

  #[Test]
  public function rate_limit_updated() {
    $target= (new Distributed([$this->testingEndpoint(1000)]))->distribute();
    $target->api('/chat/completions')->invoke(['prompt' => 'Test']);

    Assert::equals(999, $target->rateLimit->remaining);
  }

  #[Test]
  public function distributes_to_endpoint_with_most_remaining_requests() {
    $a= $this->testingEndpoint(1000);
    $b= $this->testingEndpoint(100);

    // Invoke both as the limits are not updated until after a request
    $a->api('/chat/completions')->invoke(['prompt' => 'Test a']);
    $b->api('/chat/completions')->invoke(['prompt' => 'Test b']);

    Assert::equals($a, (new Distributed([$a, $b]))->distribute());
  }

  #[Test]
  public function invokes_endpoint_with_most_remaining_requests() {
    $a= $this->testingEndpoint(1000);
    $b= $this->testingEndpoint(997);

    // Invoke both as the limits are not updated until after a request
    $a->api('/chat/completions')->invoke(['prompt' => 'Test a']);
    $b->api('/chat/completions')->invoke(['prompt' => 'Test b']);
    $before= [$a->rateLimit->remaining, $b->rateLimit->remaining];

    // Now invoke in a distributed manner
    $distributed= new Distributed([$a, $b]);
    for ($i= 0; $i < 3; $i++) {
      $distributed->api('/chat/completions')->invoke(['prompt' => 'Test']);
    }

    Assert::equals($before[0] - 3, $a->rateLimit->remaining);
    Assert::equals($before[1], $b->rateLimit->remaining);
  }
}