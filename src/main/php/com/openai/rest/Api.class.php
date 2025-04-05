<?php namespace com\openai\rest;

use com\openai\tools\Functions;
use webservices\rest\{RestResource, RestResponse, RestUpload, UnexpectedStatus};

/** @see https://platform.openai.com/docs/guides/responses-vs-chat-completions */
class Api {
  const JSON= 'application/json';
  const EVENTS= 'text/event-stream';

  private $resource, $rateLimit;
  private $adapt= null;

  /** Creates a new API instance from a given REST resource */
  public function __construct(RestResource $resource, RateLimit $rateLimit) {
    $this->resource= $resource;
    $this->rateLimit= $rateLimit;

    // In the legacy completions API, streaming requires an option to include usage and tools
    // are formatted in a substructure, see https://github.com/xp-forge/openai/issues/20
    if (0 === substr_compare($resource->uri()->path(), '/completions', -12)) {
      $structure= function($tools) {
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
      };
      $this->adapt= function($payload) use($structure) {
        if ($payload['stream'] ?? null) $payload['stream_options']= ['include_usage' => true];
        if ($payload['tools'] ?? null) $payload['tools']= [...$structure($payload['tools'])];
        return $payload;
      };
    }
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
    $r= $this->resource->post($this->adapt ? ($this->adapt)($payload) : $payload, $mime);
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
    $this->resource->accepting(self::EVENTS);
    return new EventStream($this->transmit(['stream' => true] + $payload)->stream());
  }

  /** Yields events from a streamed response */
  public function events(array $payload): Events {
    $this->resource->accepting(self::EVENTS);
    return new Events($this->transmit(['stream' => true] + $payload)->stream());
  }
}