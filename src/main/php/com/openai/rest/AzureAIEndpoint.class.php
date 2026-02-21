<?php namespace com\openai\rest;

use util\URI;
use webservices\rest\Endpoint;

/**
 * Azure OpenAI APIs. Usage slightly differs from their public OpenAI counterpart,
 * as the API Key must be passed in a special header, and an API version must be
 * included in the query string.
 *
 * @test com.openai.unittest.AzureAIEndpointTest
 */
class AzureAIEndpoint extends RestEndpoint {
  public $version;

  /**
   * Creates a new OpenAI endpoint
   *
   * @param  string|util.URI|webservices.rest.Endpoint
   * @param  ?string $version API version
   */
  public function __construct($arg, $version= null) {
    if ($arg instanceof Endpoint) {
      $this->version= $version;
      parent::__construct($arg);
    } else {
      $uri= $arg instanceof URI ? $arg : new URI($arg);
      $this->version= $version ?? $uri->param('api-version');
      parent::__construct((new Endpoint($uri))->with(['Authorization' => null, 'API-Key' => $uri->user()]));
    }
  }

  /** @return string */
  private function versioned() {
    return null === $this->version ? '' : '?api-version='.$this->version;
  }

  /** Returns an API */
  public function api(string $path, array $segments= []): Api {
    return new Api(
      $this->endpoint->resource(ltrim($path, '/').$this->versioned(), $segments),
      $this->rateLimit
    );
  }

  /** @return string */
  public function toString() {
    return nameof($this).'(->'.$this->endpoint->base().$this->versioned().')';
  }
}