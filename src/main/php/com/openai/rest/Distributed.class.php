<?php namespace com\openai\rest;

/**
 * Allows distributing API requests to different endpoints.
 *
 * @test  com.openai.unittest.DistributedTest
 */
class Distributed extends ApiEndpoint {
  private $endpoints;

  /** @param com.openai.rest.ApiEndpoint[] $endpoints */
  public function __construct(array $endpoints) {
    $this->endpoints= $endpoints;
  }

  /**
   * Provides a log category for tracing requests
   *
   * @param  ?util.log.LogCategory $cat
   */
  public function setTrace($cat) {
    foreach ($this->endpoints as $endpoint) {
      $endpoint->setTrace($cat);
    }
  }

  /** Distributes API calls */
  public function distribute(): ApiEndpoint {
    return $this->endpoints[rand(0, sizeof($this->endpoints) - 1)];
  }

  /** Distributes request and returns an API */
  public function api(string $path, array $segments= []): Api {
    return $this->distribute()->api($path, $segments);
  }
}