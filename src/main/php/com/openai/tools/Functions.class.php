<?php namespace com\openai\tools;

use ReflectionFunction;
use com\openai\tools\{Param, Context};
use lang\reflection\TargetException;
use lang\{Type, Reflection, Value, IllegalArgumentException};
use util\Objects;

/**
 * Function calling
 *
 * @test com.openai.unittest.FunctionsTest
 */
class Functions extends Definitions implements Value {
  private $definitions= [];

  /** Creates functions from given definitions */
  public function __construct(parent... $definitions) {
    $this->definitions= $definitions;
  }

  /** Yields descriptions for all methods registered */
  public function schema(): iterable {
    foreach ($this->definitions as $definitions) {
      yield from $definitions->schema();
    }
  }

  /** @return ?ReflectionFunction */
  public function invocable(string $name, ?string $namespace= null) {
    foreach ($this->definitions as $definitions) {
      if ($invocable= $definitions->invocable($name, $namespace)) return $invocable;
    }
    return null;
  }

  /** @return string */
  public function toString() {
    $s= nameof($this);
    if (empty($this->definitions)) return $s.'(0) []';

    $s= nameof($this).'('.sizeof($this->definitions).") [\n";
    foreach ($this->definitions as $definitions) {
      $s.= '  '.Objects::stringOf($definitions, '  ')."\n";
    }
    return $s.']';
  }
}