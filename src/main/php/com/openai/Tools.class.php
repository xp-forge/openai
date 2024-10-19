<?php namespace com\openai;

use Generator;
use com\openai\tools\{Param, Context};
use lang\{Type, Reflection, Value, IllegalArgumentException};
use lang\reflection\TargetException;
use util\{Comparison, Objects};

/**
 * Tool calling
 *
 * @test com.openai.unittest.ToolsTest
 */
class Tools implements Value {
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

  /**
   * Invokes the given tool from a tool call
   *
   * @param  string $call
   * @param  [:var] $arguments
   * @param  [:var] $context
   * @return var
   * @throws lang.Throwable
   */
  public function invoke(string $call, array $arguments, array $context= []) {
    try {
      sscanf($call, "%[^_]_%[^\r]", $namespace, $name);
      $methods= $this->methods[$namespace]
        ?? throw new IllegalArgumentException("Unknown namespace {$namespace}")
      ;
      $method= $methods[$name]
        ?? throw new IllegalArgumentException("Unknown function {$name} in {$namespace}")
      ;

      // Lazily create instance
      list(&$instance, $new)= $this->instances[$namespace];
      $instance??= $new();

      $pass= [];
      foreach ($methods[$name]->parameters() as $param => $reflect) {
        if ($reflect->annotations()->provides(Context::class)) {
          $ptr= &$context[$param];
        } else {
          $ptr= &$arguments[$param];
        }

        $pass[]= $ptr ?? ($reflect->optional()
          ? $reflect->default()
          : throw new IllegalArgumentException("Missing argument {$param} for {$call}")
        );
      }
      return $methods[$name]->invoke($instance, $pass);
    } catch (TargetException $e) {
      throw $e->getCause();
    }
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