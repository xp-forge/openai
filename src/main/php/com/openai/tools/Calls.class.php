<?php namespace com\openai\tools;

use Throwable as Any;
use lang\reflection\TargetException;
use lang\{Type, Throwable, IllegalArgumentException};
use util\data\Marshalling;

/**
 * Function calls
 *
 * @test  com.openai.unittest.CallsTest
 * @test  com.openai.unittest.MarshallingTest
 */
class Calls {
  private $functions, $marshalling;
  private $catch= null;

  /** Creates a new instance */
  public function __construct(Functions $functions) {
    $this->functions= $functions;
    $this->marshalling= new Marshalling();
  }

  /**
   * Pass an error handler
   *
   * @param  function(lang.Throwable): var $handler
   * @return self
   */
  public function catching(callable $handler): self {
    $this->catch= $handler;
    return $this;
  }

  /**
   * Converts a Throwable instance to an error representation
   *
   * @param  lang.Throwable
   * @return var
   */
  private function error($t) {
    return ($this->catch ? ($this->catch)($t) : null) ?? [
      'error'   => nameof($t),
      'message' => $t->getMessage()
    ];
  }

  /**
   * Yields argument types and values to pass
   *
   * @param  lang.reflection.Method
   * @param  [:var] $named
   * @param  [:var] $context
   * @return iterable
   */
  private function pass($method, $named, $context) {
    foreach ($method->parameters() as $param => $reflect) {
      $annotations= $reflect->annotations();
      if ($annotation= $annotations->type(Context::class)) {
        $ptr= &$context;
        $name= $annotation->argument('name') ?? $annotation->argument(0) ?? $param;
      } else {
        $ptr= &$named;
        $name= $param;
      }

      // Support NULL inside context or arguments
      if (array_key_exists($name, $ptr)) {
        yield $reflect->constraint()->type() => $ptr[$name];
      } else if ($reflect->optional()) {
        yield Type::$VAR => $reflect->default();
      } else {
        throw new IllegalArgumentException("Missing argument {$name} for {$method->name()}");
      }
    }
  }

  /**
   * Invoke the function with named arguments and a given context
   *
   * @param  string $name
   * @param  [:var] $named
   * @param  [:var] $context
   * @return var
   * @throws lang.IllegalArgumentException
   * @throws lang.reflect.TargetException
   */
  public function invoke($name, $named, $context= []) {
    list($instance, $method)= $this->functions->target($name);

    $pass= [];
    foreach ($this->pass($method, $named, $context) as $value) {
      $pass[]= $value;
    }

    return $method->invoke($instance, $pass);
  }

  /**
   * Call the function, including handling JSON de- and encoding and converting
   * caught exceptions to a serializable form.
   *
   * @param  string $name
   * @param  string $arguments
   * @param  [:var] $context
   * @return string
   */
  public function call($name, $arguments, $context= []) {
    try {
      list($instance, $method)= $this->functions->target($name);

      $named= json_decode($arguments, null, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
      $pass= [];
      foreach ($this->pass($method, $named, $context) as $type => $value) {
        $pass[]= $this->marshalling->unmarshal($value, $type);
      }
      
      $result= $this->marshalling->marshal($method->invoke($instance, $pass));
    } catch (TargetException $e) {
      $result= $this->error($e->getCause());
    } catch (Any $e) {
      $result= $this->error(Throwable::wrap($e));
    }

    return json_encode($result);
  }
}