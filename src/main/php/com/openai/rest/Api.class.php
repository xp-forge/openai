<?php namespace com\openai\rest;

use webservices\rest\{RestResource, RestResponse, RestUpload, UnexpectedStatus};

class Api {
  const JSON= 'application/json';
  const EVENTS= 'text/event-stream';

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
    $this->rateLimit->update($r->header('x-ratelimit-remaining-requests'));
    if (200 === $r->status()) return $r;

    throw new UnexpectedStatus($r);
  }

  /**
   * Starts an upload
   *
   * @param  [:string] $params
   * @return com.openai.rest.Upload
   */
  public function open($params= []): Upload {
    $upload= $this->resource->upload('POST');
    foreach ($params as $name => $value) {
      $upload->pass($name, $value);
    }
    return new Upload($upload, $this->rateLimit);
  }

  /** Invokes API and returns result */
  public function invoke(array $payload) {
    $this->resource->accepting(self::JSON);
    return $this->transmit($payload)->value();
  }

  /** Streams API response */
  public function stream(array $payload): EventStream {
    static $stream= ['stream' => true, 'stream_options' => ['include_usage' => true]];

    $this->resource->accepting(self::EVENTS);
    return new EventStream($this->transmit($stream + $payload)->stream());
  }

  /** Yields events from a streamed response */
  public function events(array $payload): Events {
    static $stream= ['stream' => true];

    $this->resource->accepting(self::EVENTS);
    return new Events($this->transmit($stream + $payload)->stream());
  }
}