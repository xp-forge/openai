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
   * @return webservices.rest.RestResponse
   */
  public function transmit($payload): RestResponse {
    $r= $this->resource->post($payload, self::JSON);
    if (200 === $r->status()) return $r;

    throw new UnexpectedStatus($r);
  }

  /** Invokes API and returns result */
  public function invoke(array $payload) {
    $r= $this->resource
      ->accepting(self::JSON)
      ->post(['stream' => false] + $payload, self::JSON)
    ;
    if (200 === $r->status()) return $r->value();

    throw new UnexpectedStatus($r);
  }

  /** Streams API response */
  public function stream(array $payload): EventStream {
    $r= $this->resource
      ->accepting('text/event-stream')
      ->post(['stream' => true] + $payload, self::JSON)
    ;
    if (200 === $r->status()) return new EventStream($r->stream());

    throw new UnexpectedStatus($r);
  }
}