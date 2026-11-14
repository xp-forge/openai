<?php namespace com\openai\tools;

use ReflectionFunction;
use lang\Reflection;

/** @test com.openai.unittest.NamedTest */
class Named extends Definitions {
  private $functions= [];
  public $description= null;

  /** @param [:callable]|object $arg */
  public function __construct($arg, ?string $description= null) {
    if (is_array($arg)) {
      foreach ($arg as $name => $closure) {
        $this->functions[$name]= new ReflectionFunction($closure);
      }
      $this->description= $description;
    } else {
      $type= Reflection::type($arg);

      // Use API doc comment for description if none is given
      $this->description= $description ?? $type->comment();

      // Constructors, destructors, interceptors and other special methods use a `__` prefix
      foreach ($type->methods() as $method) {
        $m= $method->modifiers();
        if ($m->isPublic() && !$m->isStatic()) {
          $this->functions[$method->name()]= new ReflectionFunction($method->closure($arg));
        }
      }
    }
  }

  /** Returns schema for these functions */
  public function schema($prefix= ''): iterable {
    foreach ($this->functions as $name => $reflect) {
      $properties= $required= [];
      foreach ($reflect->getParameters() as $param) {
        if ($param->getAttributes(Context::class)) {
          continue;
        } else if ($attributes= $param->getAttributes(Param::class)) {
          $properties[$param->name]= (new Param(...$attributes[0]->getArguments()))->schema();
        } else {
          $properties[$param->name]= ['type' => 'string', 'description' => ucfirst($param->name)];
        }
        $param->isOptional() || $required[]= $param->name;
      }

      yield new Definition(
        $prefix.$name,
        preg_replace(
          ['/^\/\*\* ?/', '/\n\s+\* ?/', '/ ?\*\/$/'], ['', "\n", ''],
          $reflect->getDocComment() ?: $name
        ),
        [
          'type'        => 'object',
          'properties'  => $properties ?: (object)[],
          'required'    => $required,
        ],
      );
    }
  }

  /** @return ?ReflectionFunction */
  public function invocable(string $name, ?string $namespace= null) {
    return null === $namespace ? $this->functions[$name] ?? null : null;
  }
}