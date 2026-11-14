<?php namespace com\openai\tools;

use lang\Reflection;

class Instances {
  public $type, $new;

  /**
   * Constructor. Use `new` or `single` to create instances.
   *
   * @param  lang.reflection.Type $type
   * @param  function(): object $new
   */
  private function __construct($type, $new) {
    $this->type= $type;
    $this->new= $new;
  }

  /**
   * Creates a new instance for every call
   *
   * @param  string|lang.reflection.Type|lang.XPClass $arg
   * @param  function(lang.reflection.Type): object $newInstance
   */
  public static function new($arg, ?callable $newInstance= null): self {
    $type= Reflection::type($arg);
    return new self($type, function() use($type, $newInstance) {
      return $newInstance ? $newInstance($type) : $type->newInstance();
    });
  }

  /**
   * Creates a single instance
   *
   * @param  string|lang.reflection.Type|lang.XPClass $arg
   * @param  function(lang.reflection.Type): object $newInstance
   */
  public static function single($arg, ?callable $newInstance= null): self {
    $type= Reflection::type($arg);
    return new self($type, function() use($type, $newInstance) {
      static $instance= null;
      return $instance??= $newInstance ? $newInstance($type) : $type->newInstance();
    });
  }
}