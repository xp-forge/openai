<?php namespace com\openai\tools;

use lang\Value;
use util\Comparison;

/** Base class for Named, Grouped and Namespaced */
abstract class Definitions implements Value {
  use Comparison;

  /** Returns calls instance wired to this definition */
  public function calls(): Calls { return new Calls($this); }

  /** Returns schema for these functions */
  public abstract function schema(): iterable;

  /** @return ?ReflectionFunction */
  public abstract function invocable(string $name, ?string $namespace= null);
}