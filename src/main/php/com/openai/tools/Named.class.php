<?php namespace com\openai\tools;

use ReflectionFunction, ReflectionMethod;
use lang\Reflection;

/** @test com.openai.unittest.NamedTest */
class Named extends Definitions {
  private $functions= [];
  public $description= null;

  /** @param [:callable]|com.openai.tools.Instances|object $arg */
  public function __construct($arg, ?string $description= null) {
    if (is_array($arg)) {
      foreach ($arg as $name => $closure) {
        $this->functions[$name]= [new ReflectionFunction($closure), null];
      }
      $this->description= $description;
    } else if ($arg instanceof Instances) {
      $this->description= $description ?? $arg->type->comment();
      foreach ($arg->type->methods() as $method) {
        $m= $method->modifiers();
        if ($m->isPublic() && !$m->isStatic()) {
          $this->functions[$method->name()]= [new ReflectionMethod($arg->type->literal(), $method->name()), $arg->new];
        }
      }
    } else {
      $type= Reflection::type($arg);
      foreach ($type->methods() as $method) {
        $m= $method->modifiers();
        if ($m->isPublic() && !$m->isStatic()) {
          $this->functions[$method->name()]= [new ReflectionFunction($method->closure($arg)), null];
        }
      }
      $this->description= $description ?? $type->comment();
    }
  }

  /** Returns schema for these functions */
  public function schema($prefix= ''): iterable {
    foreach ($this->functions as $name => [$reflect, $instance]) {
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
    if (null === $namespace && ($function= $this->functions[$name] ?? null)) {
      return $function[1]
        ? new ReflectionFunction($function[0]->getClosure($function[1]()))
        : $function[0]
      ;
    }
    return null;
  }

  /** @return string */
  public function toString() {
    $s= nameof($this);
    null === $this->description || $s.= "('{$this->description}')";
    if (empty($this->functions)) return $s.' {}';

    $s.= " {\n";
    foreach ($this->schema() as $definition) {
      $s.= '  '.$definition->name.'(): '.$definition->description."\n";
    }
    return $s.'}';
  }
}