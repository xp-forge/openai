<?php namespace com\openai\rest;

use webservices\rest\Endpoint;

/**
 * OpenAI REST API endpoint
 *
 * @see  https://platform.openai.com/docs/api-reference/authentication
 * @test com.openai.unittest.OpenAIEndpointTest
 */
class OpenAIEndpoint extends RestEndpoint {

  /**
   * Creates a new OpenAI endpoint
   *
   * @param  string|util.URI|webservices.rest.Endpoint
   * @param  ?string $organization
   * @param  ?string $project
   */
  public function __construct($arg, $organization= null, $project= null) {
    parent::__construct($arg instanceof Endpoint ? $arg : new Endpoint($arg));

    // Pass optional organization and project IDs
    $headers= [];
    $organization && $headers['OpenAI-Organization']= $organization;
    $project && $headers['OpenAI-Project']= $project;
    $headers && $this->endpoint->with($headers);
  }

  /** Returns an API */
  public function api(string $path, array $segments= []): Api {
    return new Api($this->endpoint->resource(ltrim($path, '/'), $segments), $this->rateLimit);
  }

  /** @return string */
  public function toString() { return nameof($this).'(->'.$this->endpoint->base().')'; }
}