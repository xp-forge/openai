<?php namespace com\openai\rest;

use webservices\rest\{RestResource, RestResponse, UnexpectedStatus};

class Api {
  const JSON= 'application/json';
  const STREAMING= ['stream' => true, 'stream_options' => ['include_usage' => true]];

  private $resource, $rateLimit;

  /** Creates a new API instance from a given REST resource */
  public function __construct(RestResource $resource, RateLimit $rateLimit) {
    $this->resource= $resource;
    $this->rateLimit= $rateLimit;
  }

  /**
   * Transmits given payload to the API and returns response
   *
   * @param  var $payload
   * @param  string $mime Defaults to JSON
   * @return webservices.rest.RestResponse
   * @throws webservices.rest.UnexpectedStatus
   */
  public function transmit($payload, $mime= self::JSON): RestResponse {
    $r= $this->resource->post($payload, $mime);

    // Update rate limit if header is present
    if (null !== ($remaining= $r->header('x-ratelimit-remaining-requests'))) {
      $this->rateLimit->remaining= (int)$remaining;
    }

    if (200 === $r->status()) return $r;

    throw new UnexpectedStatus($r);
  }

  /** Invokes API and returns result */
  public function invoke(array $payload) {
    $this->resource->accepting(self::JSON);
    return $this->transmit($payload)->value();
  }

  /** Streams API response */
  public function stream(array $payload): EventStream {
    $this->resource->accepting('text/event-stream');
    return new EventStream($this->transmit(self::STREAMING + $payload)->stream());
  }
}