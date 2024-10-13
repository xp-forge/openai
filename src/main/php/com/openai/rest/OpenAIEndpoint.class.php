<?php namespace com\openai\rest;

use webservices\rest\Endpoint;

class OpenAIEndpoint {
  private $endpoint;

  /**
   * Creates a new OpenAI endpoint
   *
   * @param  string|util.URI|webservices.rest.Endpoint
   */
  public function __construct($arg) {
    $this->endpoint= $arg instanceof Endpoint ? $arg : new Endpoint($arg);
  }

  /** Returns an API */
  public function api(string $path, array $segments= []): Api {
    return new Api($this->endpoint->resource(ltrim($path, '/'), $segments));
  }
}