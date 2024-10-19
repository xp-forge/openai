<?php namespace com\openai\rest;

use util\log\Traceable;
use webservices\rest\Endpoint;

/**
 * OpenAI REST API endpoint
 *
 * @test com.openai.unittest.OpenAIEndpointTest
 */
class OpenAIEndpoint implements Traceable {
  private $endpoint;

  /**
   * Creates a new OpenAI endpoint
   *
   * @param  string|util.URI|webservices.rest.Endpoint
   */
  public function __construct($arg) {
    $this->endpoint= $arg instanceof Endpoint ? $arg : new Endpoint($arg);
  }

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
    return new Api($this->endpoint->resource(ltrim($path, '/'), $segments));
  }
}