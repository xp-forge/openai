<?php namespace com\openai\unittest;

use com\openai\rest\{Distributed, ByRemainingRequests};
use test\{Assert, Before, Expect, Test};

class ByRemainingRequestsTest {
  use TestingEndpoint;

  private $fixture;

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
  public function chooses_randomly_if_ratelimit_zero() {
    $a= $this->testingEndpoint(0);
    $b= $this->testingEndpoint(0);

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

    Assert::equals(997, $a->rateLimit()->remaining);
    Assert::equals(997, $b->rateLimit()->remaining);
  }
}