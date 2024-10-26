<?php namespace com\openai\tools;

use com\openai\tools\{Param, Context};
use lang\reflection\TargetException;
use lang\{Type, Reflection, Value, IllegalArgumentException};
use util\{Comparison, Objects};

/**
 * Function calling
 *
 * @test com.openai.unittest.FunctionsTest
 */
class Functions implements Value {
  use Comparison;

  private $instances= [];
  private $methods= [];

  /**
   * Registers public instance methods for a given implementation
   *
   * @param  string $namespace
   * @param  string|object $impl
   * @return self
   */
  private function methods($namespace, $impl) {
    $this->methods[$namespace]= [];
    foreach (Reflection::type($impl)->methods() as $name => $method) {
      $mod= $method->modifiers();
      if ($mod->isStatic() || !$mod->isPublic()) continue;

      $this->methods[$namespace][$name]= $method;
    }
    return $this;
  }

  /** Registers the given instance using a defined namespace */
  public function register(string $namespace, object $instance): self {
    $this->instances[$namespace]= [$instance, null];
    return $this->methods($namespace, $instance);
  }

  /**
   * Register the given type using a defined namespace
   *
   * @param  string $namespace
   * @param  string|lang.Type $impl
   * @param  ?function(lang.Type): object $new
   * @return self
   */
  public function with(string $namespace, $impl, $new= null): self {
    $t= $impl instanceof Type ? $impl : Type::forName($impl);
    $this->instances[$namespace]= [null, fn() => $new ? $new($t) : $t->newInstance()];
    return $this->methods($namespace, $impl);
  }

  /** Selects functions matching the given selectors */
  public function select(array $selectors): self {
    $self= new self();
    foreach ($selectors as $selector) {
      [$namespace, $name]= explode('_', $selector);
      if (!isset($this->methods[$namespace])) continue;

      $methods= $this->methods[$namespace];
      if ('*' === $name) {
        $self->methods[$namespace]= $methods;
      } else if ($method= $methods[$name] ?? null) {
        $self->methods[$namespace]??= [];
        $self->methods[$namespace][$name]= $method;
      }
    }
    return $self;
  }

  /** Yields descriptions for all methods registered */
  public function schema(): iterable {
    foreach ($this->methods as $namespace => $methods) {
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

        yield $namespace.'_'.$name => [
          'description' => $method->comment() ?? ucfirst($name),
          'input'       => [
            'type'        => 'object',
            'properties'  => $properties ?: (object)[],
            'required'    => $required,
          ],
        ];
      }
    }
  }

  /** @return com.openai.tools.Calls */
  public function calls() { return new Calls($this); }

  /**
   * Returns target for a given call
   *
   * @param  string $call
   * @return var[]
   * @throws lang.IllegalArgumentException if there is no such target registered
   */
  public function target($call) {
    sscanf($call, "%[^_]_%[^\r]", $namespace, $name);
    if (null === ($method= $this->methods[$namespace][$name] ?? null)) {
      throw new IllegalArgumentException(isset($this->methods[$namespace])
        ? "Unknown function {$name} in {$namespace}"
        : "Unknown namespace {$namespace}"
      );
    }

    // Lazily create instance if not set
    return [$this->instances[$namespace][0] ?? $this->instances[$namespace][1](), $method];
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