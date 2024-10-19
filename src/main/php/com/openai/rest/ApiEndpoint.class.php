<?php namespace com\openai\rest;

use webservices\rest\Endpoint;

/** Base class for AzureAI and OpenAI endpoints */
abstract class ApiEndpoint {
  protected $endpoint;

  /** Creates a new API endpoint */
  public function __construct(Endpoint $endpoint) { $this->endpoint= $endpoint; }

  /** @return util.URI */
  public function base() { return $this->endpoint->base(); }

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
  public abstract function api(string $path, array $segments= []): Api;
}