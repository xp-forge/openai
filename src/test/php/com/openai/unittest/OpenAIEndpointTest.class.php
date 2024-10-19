<?php namespace com\openai\unittest;

use com\openai\rest\OpenAIEndpoint;
use test\{Assert, Test};

class OpenAIEndpointTest extends ApiEndpointTest {
  const URI= 'https://sk-test@api.openai.example.com/v1';

  /** @return com.openai.rest.ApiEndpoint */
  protected function fixture(... $args) { return new OpenAIEndpoint(...$args); }

  #[Test]
  public function can_create() {
    $this->fixture(self::URI);
  }

  #[Test]
  public function authorization_header_set() {
    Assert::equals('Bearer sk-test', $this->fixture(self::URI)->headers()['Authorization']);
  }
}