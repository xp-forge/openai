<?php namespace com\openai\unittest;

use com\openai\rest\{Distributed, OpenAIEndpoint, ByRemainingRequests};
use test\{Assert, Before, Expect, Test};
use webservices\rest\TestEndpoint;

class ByRemainingRequestsTest {
  private $fixture;

  /** Returns a testing API endpoint */
  private function testingEndpoint($remaining): OpenAIEndpoint {
    $endpoint= new OpenAIEndpoint(new TestEndpoint([
      'POST /chat/completions' => function($call) use(&$remaining) {
        $remaining--;
        return $call->respond(
          200, 'OK',
          ['x-ratelimit-remaining-requests' => max(0, $remaining), 'Content-Type' => 'application/json'],
          '{"choices":[{"message":{"role":"assistant","content":"Test"}}]}'
        );
      }
    ]));

    // Normally this is not done until after the API has been invoked, for
    // ease of testing purposes we'll set it here.
    $endpoint->rateLimit->remaining= $remaining;
    return $endpoint;
  }

  #[Before]
  public function fixture() {
    $this->fixture= new ByRemainingRequests();
  }

  #[Test]
  public function using_single_endpoint() {
    $a= $this->testingEndpoint(1000);

    Assert::equals($a, $this->fixture->distribute([$a]));
  }

  #[Test]
  public function distributes_to_endpoint_with_most_remaining_requests() {
    $a= $this->testingEndpoint(1000);
    $b= $this->testingEndpoint(100);

    Assert::equals($a, $this->fixture->distribute([$a, $b]));
  }

  #[Test]
  public function chooses_randomly_if_ratelimit_unknown() {
    $a= $this->testingEndpoint(null);
    $b= $this->testingEndpoint(null);

    Assert::true(in_array($this->fixture->distribute([$a, $b]), [$a, $b], true));
  }

  #[Test]
  public function invokes_endpoint_with_most_remaining_requests() {
    $a= $this->testingEndpoint(1000);
    $b= $this->testingEndpoint(997);

    // Invoke in a distributed manner. All requests will go to $a, since it
    // has more remaining requests than $b
    $distributed= new Distributed([$a, $b], $this->fixture);
    for ($i= 0; $i < 3; $i++) {
      $distributed->api('/chat/completions')->invoke(['prompt' => 'Test']);
    }

    Assert::equals(997, $a->rateLimit->remaining);
    Assert::equals(997, $b->rateLimit->remaining);
  }
}