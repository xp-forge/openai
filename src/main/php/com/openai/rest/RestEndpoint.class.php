<?php namespace com\openai\rest;

use com\openai\tools\{Tools, Functions};

/** Base class for OpenAI and AzureAI implementations */
abstract class RestEndpoint extends ApiEndpoint {
  protected $endpoint, $rateLimit;

  /** @param webservices.rest.Endpoint */
  public function __construct($endpoint) {
    $this->endpoint= $endpoint;
    $this->endpoint->marshalling->mapping(Tools::class, function($tools) {
      foreach ($tools->selection as $select) {
        if ($select instanceof Functions) {
          foreach ($select->schema() as $name => $function) {
            yield ['type' => 'function', 'function' => [
              'name'        => $name,
              'description' => $function['description'],
              'parameters'  => $function['input'],
            ]];
          }
        } else {
          yield $select;
        }
      }
    });
    $this->rateLimit= new RateLimit();
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
}