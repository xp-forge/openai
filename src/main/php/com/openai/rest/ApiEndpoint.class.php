<?php namespace com\openai\rest;

use util\log\Traceable;

/** Base class for Distributed, AzureAI and OpenAI endpoints */
abstract class ApiEndpoint implements Traceable {

  /** Returns an API */
  public abstract function api(string $path, array $segments= []): Api;
}