<?php namespace com\openai\rest;

use lang\Value;
use util\Comparison;
use util\log\Traceable;

/** Base class for Distributed, AzureAI and OpenAI endpoints */
abstract class ApiEndpoint implements Traceable, Value {
  use Comparison;

  /** Returns an API */
  public abstract function api(string $path, array $segments= []): Api;
}