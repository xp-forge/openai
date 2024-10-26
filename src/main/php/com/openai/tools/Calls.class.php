<?php namespace com\openai\tools;

use Throwable as Any;
use lang\reflection\TargetException;
use lang\{Throwable, IllegalArgumentException};

/** @test com.openai.unittest.CallsTest */
class Calls {
  private $functions;
  private $catch= null;

  /** Creates a new instance */
  public function __construct(Functions $functions) { $this->functions= $functions; }

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
   * Invoke the function with named arguments and a given context
   *
   * @param  string $name
   * @param  [:var] $arguments
   * @param  [:var] $context
   * @return var
   * @throws lang.IllegalArgumentException
   * @throws lang.reflect.TargetException
   */
  public function invoke($name, $arguments, $context= []) {
    list($instance, $method)= $this->functions->target($name);

    $pass= [];
    foreach ($method->parameters() as $param => $reflect) {
      $annotations= $reflect->annotations();
      if ($annotation= $annotations->type(Context::class)) {
        $ptr= &$context;
        $named= $annotation->argument('name') ?? $annotation->argument(0) ?? $param;
      } else {
        $ptr= &$arguments;
        $named= $param;
      }

      // Support NULL inside context or arguments
      if (array_key_exists($named, $ptr)) {
        $pass[]= $ptr[$named];
      } else if ($reflect->optional()) {
        $pass[]= $reflect->default();
      } else {
        throw new IllegalArgumentException("Missing argument {$named} for {$name}");
      }
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
      $result= $this->invoke($name, json_decode($arguments, null, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR), $context);
    } catch (TargetException $e) {
      $result= $this->error($e->getCause());
    } catch (Any $e) {
      $result= $this->error(Throwable::wrap($e));
    }
    return json_encode($result);
  }
}