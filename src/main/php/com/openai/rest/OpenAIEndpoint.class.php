<?php namespace com\openai\rest;

use webservices\rest\Endpoint;

/**
 * OpenAI REST API endpoint
 *
 * @test com.openai.unittest.OpenAIEndpointTest
 */
class OpenAIEndpoint extends ApiEndpoint {

  /**
   * Creates a new OpenAI endpoint
   *
   * @param  string|util.URI|webservices.rest.Endpoint
   */
  public function __construct($arg) {
    parent::__construct($arg instanceof Endpoint ? $arg : new Endpoint($arg));
  }

  /** Returns an API */
  public function api(string $path, array $segments= []): Api {
    return new Api($this->endpoint->resource(ltrim($path, '/'), $segments));
  }
}