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
class AzureAIEndpoint extends ApiEndpoint {
  private $endpoint;
  public $version, $rateLimit;

  /**
   * Creates a new OpenAI endpoint
   *
   * @param  string|util.URI|webservices.rest.Endpoint
   * @param  ?string $version API version
   */
  public function __construct($arg, $version= null) {
    if ($arg instanceof Endpoint) {
      $this->endpoint= $arg;
      $this->version= $version;
    } else {
      $uri= $arg instanceof URI ? $arg : new URI($arg);
      $this->version= $version ?? $uri->param('api-version');
      $this->endpoint= (new Endpoint($uri))->with(['Authorization' => null, 'API-Key' => $uri->user()]);
    }
    $this->rateLimit= new RateLimit();
  }

  /** @return [:var] */
  public function headers() { return $this->endpoint->headers(); }

  /**
   * Provides a log category for tracing requests
   *
   * @param  ?util.log.LogCategory $cat
   */
  public function setTrace($cat) {
    $this->endpoint->setTrace($cat);
  }

  /** Returns an API */
  public function api(string $path, array $segments= []): Api {
    return new Api(
      $this->endpoint->resource(ltrim($path, '/').'?api-version='.$this->version, $segments),
      $this->rateLimit
    );
  }

  /** @return string */
  public function toString() { return nameof($this).'(->'.$this->endpoint->base().'?api-version='.$this->version.')'; }
}