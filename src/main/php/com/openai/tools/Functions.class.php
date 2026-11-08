<?php namespace com\openai\tools;

use ReflectionFunction;
use com\openai\tools\{Param, Context};
use lang\reflection\TargetException;
use lang\{Type, Reflection, Value, IllegalArgumentException};
use util\{Comparison, Objects};

/**
 * Function calling
 *
 * @test com.openai.unittest.FunctionsTest
 */
class Functions extends Definitions implements Value {
  use Comparison;

  private $instances= [];
  private $methods= [];

  /**
   * Registers public instance methods for a given implementation
   *
   * @param  string $group
   * @param  string|object $impl
   * @return self
   */
  private function methods($group, $impl) {
    $this->methods[$group]= [];
    foreach (Reflection::type($impl)->methods() as $name => $method) {
      $m= $method->modifiers();
      if ($m->isStatic() || !$m->isPublic()) continue;

      $this->methods[$group][$name]= $method;
    }
    return $this;
  }

  /** Registers the given instance using a defined group */
  public function register(string $group, object $instance): self {
    $this->instances[$group]= [$instance, null];
    return $this->methods($group, $instance);
  }

  /**
   * Register the given type using a defined group
   *
   * @param  string $group
   * @param  string|lang.Type $impl
   * @param  ?function(lang.Type): object $new
   * @return self
   */
  public function with(string $group, $impl, $new= null): self {
    $t= $impl instanceof Type ? $impl : Type::forName($impl);
    $this->instances[$group]= [null, fn() => $new ? $new($t) : $t->newInstance()];
    return $this->methods($group, $impl);
  }

  /** Selects functions matching the given selectors */
  public function select(array $selectors): self {
    $self= new self();
    foreach ($selectors as $selector) {
      [$group, $name]= explode('_', $selector);
      if (!isset($this->methods[$group])) continue;

      $methods= $this->methods[$group];
      if ('*' === $name) {
        $self->methods[$group]= $methods;
      } else if ($method= $methods[$name] ?? null) {
        $self->methods[$group]??= [];
        $self->methods[$group][$name]= $method;
      }
    }
    return $self;
  }

  /** Yields descriptions for all methods registered */
  public function schema(): iterable {
    foreach ($this->methods as $group => $methods) {
      foreach ($methods as $name => $method) {

        // Use annotated parameters if possible
        $properties= $required= [];
        foreach ($method->parameters() as $param => $reflect) {
          $annotations= $reflect->annotations();
          if ($annotations->provides(Context::class)) {
            continue;
          } else if ($annotation= $annotations->type(Param::class)) {
            $properties[$param]= $annotation->newInstance()->schema();
          } else {
            $properties[$param]= ['type' => 'string', 'description' => ucfirst($param)];
          }
          $reflect->optional() || $required[]= $param;
        }

        yield new Definition(
          $group.'_'.$name,
          $method->comment() ?? ucfirst($name),
          [
            'type'        => 'object',
            'properties'  => $properties ?: (object)[],
            'required'    => $required,
          ],
        );
      }
    }
  }

  /** @return ?ReflectionFunction */
  public function invokeable(string $name, ?string $namespace= null) {
    sscanf($name, "%[^_]_%[^\r]", $group, $name);
    if (null === ($method= $this->methods[$group][$name] ?? null)) return null;

    // Lazily create instance if not set
    return new ReflectionFunction($method->closure(
      $this->instances[$group][0] ?? $this->instances[$group][1]()
    ));
  }

  /** @return string */
  public function toString() {
    $s= nameof($this)." [\n";
    foreach ($this->descriptions() as $name => $description) {
      $s.= '  '.$name.'() -> '.Objects::stringOf($description, '  ')."\n";
    }
    return $s.']';
  }
}