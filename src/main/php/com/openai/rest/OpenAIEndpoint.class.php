<?php namespace com\openai\rest;

use webservices\rest\Endpoint;

/**
 * OpenAI REST API endpoint
 *
 * @see  https://platform.openai.com/docs/api-reference/authentication
 * @test com.openai.unittest.OpenAIEndpointTest
 */
class OpenAIEndpoint extends ApiEndpoint {
  private $endpoint, $rateLimit;

  /**
   * Creates a new OpenAI endpoint
   *
   * @param  string|util.URI|webservices.rest.Endpoint
   * @param  ?string $organization
   * @param  ?string $project
   */
  public function __construct($arg, $organization= null, $project= null) {
    $this->endpoint= $arg instanceof Endpoint ? $arg : new Endpoint($arg);
    $this->rateLimit= new RateLimit();

    // Pass optional organization and project IDs
    $headers= [];
    $organization && $headers['OpenAI-Organization']= $organization;
    $project && $headers['OpenAI-Project']= $project;
    $headers && $this->endpoint->with($headers);
  }

  /** Returns rate limit */
  public function rateLimit(): RateLimit { return $this->rateLimit; }

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
    return new Api($this->endpoint->resource(ltrim($path, '/'), $segments), $this->rateLimit);
  }

  /** @return string */
  public function toString() { return nameof($this).'(->'.$this->endpoint->base().')'; }
}