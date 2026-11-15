<?php namespace com\openai\tools;

use lang\Reflection;

/** @test com.openai.unittest.NamedTest */
class Named extends Definitions {
  private $functions= [];
  public $description= null;

  /** @param [:callable]|com.openai.tools.Instances|object $arg */
  public function __construct($arg, ?string $description= null) {
    if (is_array($arg)) {
      foreach ($arg as $name => $closure) {
        $this->functions[$name]= [Reflection::invocable($closure), null];
      }
      $this->description= $description;
    } else if ($arg instanceof Instances) {
      $this->description= $description ?? $arg->type->comment();
      foreach ($arg->type->methods()->instance(MODIFIER_PUBLIC) as $method) {
        $this->functions[$method->name()]= [$method, $arg->new];
      }
    } else {
      $type= Reflection::type($arg);
      foreach ($type->methods()->instance(MODIFIER_PUBLIC) as $method) {
        $this->functions[$method->name()]= [$method->invocable($arg), null];
      }
      $this->description= $description ?? $type->comment();
    }
  }

  /** Returns schema for these functions */
  public function schema(): iterable {
    foreach ($this->functions as $name => [$target, $instance]) {
      $properties= $required= [];
      foreach ($target->parameters() as $param => $reflect) {
        if ($reflect->annotation(Context::class)) {
          continue;
        } else if ($annotation= $reflect->annotation(Param::class)) {
          $properties[$param]= $annotation->newInstance()->schema();
        } else {
          $properties[$param]= ['type' => 'string', 'description' => ucfirst($param)];
        }
        $reflect->optional() || $required[]= $reflect->name();
      }

      yield new Definition(
        $name,
        $target->comment() ?? null,
        [
          'type'        => 'object',
          'properties'  => $properties ?: (object)[],
          'required'    => $required,
        ],
      );
    }
  }

  /** @return ?lang.reflection.Invocable */
  public function invocable(string $name, ?string $namespace= null) {
    if (null === $namespace && ($function= $this->functions[$name] ?? null)) {
      return $function[1]
        ? $function[0]->invocable($function[1]())
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