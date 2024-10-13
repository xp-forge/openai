<?php namespace com\openai\rest;

use webservices\rest\{RestResource, UnexpectedStatus};

class Api {
  private $resource;

  /** Creates a new API instance from a given REST resource */
  public function __construct(RestResource $resource) {
    $this->resource= $resource;
  }

  /** Invokes API and returns result */
  public function invoke(array $payload) {
    $r= $this->resource
      ->accepting('application/json')
      ->post(['stream' => false] + $payload, 'application/json')
    ;
    if (200 === $r->status()) return $r->value();

    throw new UnexpectedStatus($r);
  }

  /** Streams API response */
  public function stream(array $payload): EventStream {
    $r= $this->resource
      ->accepting('text/event-stream')
      ->post(['stream' => true] + $payload, 'application/json')
    ;
    if (200 === $r->status()) return new EventStream($r->stream());

    throw new UnexpectedStatus($r);
  }
}