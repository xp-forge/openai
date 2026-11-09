<?php namespace com\openai\tools;

/** Base class for Named, Grouped and Namespaced */
abstract class Definitions {

  /** Returns calls instance wired to this definition */
  public function calls(): Calls { return new Calls($this); }

  /** Returns schema for these functions */
  public abstract function schema();

  /** @return ?ReflectionFunction */
  public abstract function invokeable(string $name, ?string $namespace= null);
}