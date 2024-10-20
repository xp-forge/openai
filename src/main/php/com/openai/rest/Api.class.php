<?php namespace com\openai\rest;

use webservices\rest\{RestResource, RestResponse, UnexpectedStatus};

class Api {
  const JSON= 'application/json';

  private $resource;

  /** Creates a new API instance from a given REST resource */
  public function __construct(RestResource $resource) {
    $this->resource= $resource;
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
    if (200 === $r->status()) return $r;

    throw new UnexpectedStatus($r);
  }

  /** Invokes API and returns result */
  public function invoke(array $payload) {
    $this->resource->accepting(self::JSON);
    return $this->transmit(['stream' => false] + $payload)->value();
  }

  /** Streams API response */
  public function stream(array $payload): EventStream {
    $this->resource->accepting('text/event-stream');
    return new EventStream($this->transmit(['stream' => true] + $payload)->stream());
  }
}