<?php namespace com\openai\rest;

use lang\IllegalArgumentException;
use util\Objects;

/**
 * Supports distributing requests over multiple endpoints to increase
 * performance, using a given distribution strategy to select target
 * endpoints.
 *
 * @test  com.openai.unittest.DistributedTest
 */
class Distributed extends ApiEndpoint {
  private $endpoints, $distribution;

  /**
   * Creates a new distributed endpoint from a list of endpoints
   *
   * @param  com.openai.rest.ApiEndpoint[] $endpoints
   * @param  com.openai.rest.Distribution $distribution
   * @throws lang.IllegalArgumentException;
   */
  public function __construct(array $endpoints, Distribution $distribution) {
    if (empty($endpoints)) {
      throw new IllegalArgumentException('Endpoints cannot be empty');
    }
    $this->endpoints= $endpoints;
    $this->distribution= $distribution;
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

  /** Distributes request and returns an API */
  public function api(string $path, array $segments= []): Api {
    return $this->distribution->distribute($this->endpoints)->api($path, $segments);
  }

  /** @return string */
  public function toString() { return nameof($this).Objects::stringOf($this->endpoints); }
}