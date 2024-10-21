<?php namespace com\openai\unittest;

use com\openai\rest\{Api, Distributed, OpenAIEndpoint};
use lang\IllegalArgumentException;
use test\{Assert, Before, Expect, Test};

class DistributedTest {
  private $endpoints;

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
}