<?php namespace com\openai\rest;

use com\openai\tools\Tools;

/** Base class for OpenAI and AzureAI implementations */
abstract class RestEndpoint extends ApiEndpoint {
  protected $endpoint, $rateLimit;

  /** @param webservices.rest.Endpoint */
  public function __construct($endpoint) {
    $this->endpoint= $endpoint;
    $this->endpoint->marshalling= Tools::marshalling(fn($definition) => [
      'type'        => 'function',
      'name'        => $definition->name,
      'description' => $definition->description,
      'parameters'  => $definition->input,
    ]);
    $this->rateLimit= new RateLimit();
  }

  /** Returns rate limit */
  public function rateLimit(): RateLimit { return $this->rateLimit; }

  /**
   * Adds headers to be sent with every request
   *
   * @param  string|[:string] $arg
   * @param  ?string $value
   * @return self
   */
  public function with($arg, $value= null) {
    $this->endpoint->with($arg, $value);
    return $this;
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
}