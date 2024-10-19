<?php namespace com\openai\unittest;

use com\openai\rest\AzureAIEndpoint;
use test\{Assert, Test};

class AzureAIEndpointTest extends ApiEndpointTest {
  const URI= 'https://1e51...@test.openai.azure.com/openai/deployments/omni';

  /** @return com.openai.rest.ApiEndpoint */
  protected function fixture($endpoint) { return new AzureAIEndpoint($endpoint); }

  #[Test]
  public function can_create() {
    $this->fixture(self::URI);
  }

  #[Test]
  public function version_extracted_from_uri() {
    Assert::equals('2024-02-01', $this->fixture(self::URI.'?api-version=2024-02-01')->version);
  }

  #[Test]
  public function api_key_header_set() {
    Assert::equals('1e51...', $this->fixture(self::URI)->headers()['API-Key']);
  }
}