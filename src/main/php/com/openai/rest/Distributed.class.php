<?php namespace com\openai\rest;

use lang\IllegalArgumentException;
use util\Objects;

/**
 * Allows distributing API requests to different endpoints.
 *
 * @test  com.openai.unittest.DistributedTest
 */
class Distributed extends ApiEndpoint {
  private $endpoints;

  /**
   * Creates a new distributed endpoint from a list of endpoints
   *
   * @param  com.openai.rest.ApiEndpoint[] $endpoints
   * @throws lang.IllegalArgumentException;
   */
  public function __construct(array $endpoints) {
    if (empty($endpoints)) {
      throw new IllegalArgumentException('Endpoints cannot be empty');
    }
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
    $max= 0;
    $most= null;
    $candidates= [];
    foreach ($this->endpoints as $i => $endpoint) {
      if (null === $endpoint->rateLimit->remaining) {
        $candidates[]= $endpoint;
      } else if ($endpoint->rateLimit->remaining > $max) {
        $most= $endpoint;
        $max= $endpoint->rateLimit->remaining;
      }
    }

    // Select between the one with the most remaining requests, including any
    // unlimited ones, and fall back to a random endpoint.
    if ($most) {
      $candidates[]= $most;
    } else if (empty($candidates)) {
      $candidates= $this->endpoints;
    }

    return $candidates[rand(0, sizeof($candidates) - 1)];
  }

  /** Distributes request and returns an API */
  public function api(string $path, array $segments= []): Api {
    return $this->distribute()->api($path, $segments);
  }

  /** @return string */
  public function toString() { return nameof($this).Objects::stringOf($this->endpoints); }
}