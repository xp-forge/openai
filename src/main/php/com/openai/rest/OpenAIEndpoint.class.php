<?php namespace com\openai\rest;

use util\URI;
use webservices\rest\Endpoint;

/**
 * OpenAI REST API endpoint
 *
 * @see  https://platform.openai.com/docs/api-reference/authentication
 * @test com.openai.unittest.OpenAIEndpointTest
 */
class OpenAIEndpoint extends RestEndpoint {

  /**
   * Creates a new OpenAI endpoint
   *
   * @param  string|util.URI|webservices.rest.Endpoint
   * @param  ?string $organization
   * @param  ?string $project
   */
  public function __construct($arg, $organization= null, $project= null) {
    if ($arg instanceof Endpoint) {
      parent::__construct($arg);
    } else {
      $uri= $arg instanceof URI ? $arg : new URI($arg);
      $organization??= $uri->param('organization');
      $project??= $uri->param('project');
      parent::__construct(new Endpoint($uri));
    }

    // Pass optional organization and project IDs
    $headers= [];
    $organization && $headers['OpenAI-Organization']= $organization;
    $project && $headers['OpenAI-Project']= $project;
    $headers && $this->endpoint->with($headers);
  }

  /** Returns an API */
  public function api(string $path, array $segments= []): Api {
    return new Api($this->endpoint->resource(ltrim($path, '/'), $segments), $this->rateLimit);
  }

  /** @return string */
  public function toString() {
    $headers= $this->endpoint->headers();
    $query= '';
    if ($value= $headers['OpenAI-Organization'] ?? null) $query.= '&organization='.$value;
    if ($value= $headers['OpenAI-Project'] ?? null) $query.= '&project='.$value;

    return nameof($this).'(->'.$this->endpoint->base().($query ? '?'.substr($query, 1) : '').')';
  }
}