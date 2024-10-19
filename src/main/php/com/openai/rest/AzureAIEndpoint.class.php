<?php namespace com\openai\rest;

use util\URI;
use webservices\rest\Endpoint;

/**
 * Azure OpenAI REST API endpoint
 *
 * @test com.openai.unittest.AzureAIEndpointTest
 */
class AzureAIEndpoint extends ApiEndpoint {
  public $version;

  /**
   * Creates a new OpenAI endpoint
   *
   * @param  string|util.URI|webservices.rest.Endpoint
   * @param  ?string $version API version
   */
  public function __construct($arg, $version= null) {
    if ($arg instanceof Endpoint) {
      parent::__construct($arg);
      $this->version= $version;
    } else {
      $uri= $arg instanceof URI ? $arg : new URI($arg);
      $this->version= $version ?? $uri->param('api-version');
      parent::__construct((new Endpoint($uri))->with(['Authorization' => null, 'API-Key' => $uri->user()]));
    }
  }

  /** Returns an API */
  public function api(string $path, array $segments= []): Api {
    return new Api($this->endpoint->resource(ltrim($path, '/').'?api-version='.$this->version, $segments));
  }
}