<?php namespace com\openai\tools;

use Throwable as Any;
use lang\reflection\TargetException;
use lang\{Throwable, IllegalArgumentException};
use text\json\Json;
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
  public function __construct(Definitions $functions) {
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
   * @param  lang.Throwable $t
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
   * @param  [:var] $named
   * @param  [:var] $context
   * @param  ?string $namespace
   * @return var
   * @throws lang.IllegalArgumentException
   * @throws com.openai.tools.CannotInvoke
   */
  public function invoke(string $name, array $named= [], array $context= [], ?string $namespace= null) {
    $compound= $namespace ? $namespace.'::'.$name : $name;
    if (null === ($invocable= $this->functions->invocable($name, $namespace))) {
      throw new IllegalArgumentException("Unknown function '{$compound}'");
    }

    $pass= [];
    foreach ($invocable->parameters() as $param => $reflect) {
      if ($annotation= $reflect->annotation(Context::class)) {
        $arguments= $annotation->arguments();
        $ptr= &$context;
        $select= $arguments['name'] ?? $arguments[0] ?? $param;
      } else {
        $ptr= &$named;
        $select= $param;
      }

      if (array_key_exists($select, $ptr)) {
        $pass[$param]= $this->marshalling->unmarshal($ptr[$select], $reflect->constraint()->type());
      } else if ($reflect->optional()) {
        $pass[$param]= $reflect->default();
      } else {
        throw new CannotInvoke("Missing argument '{$select}' for function '{$compound}'");
      }
    }

    try {
      return $this->marshalling->marshal($invocable->invoke($pass));
    } catch (TargetException $t) {
      throw new CannotInvoke("Exception while invoking function '{$compound}'", $t->getCause());
    }
  }

  /**
   * Call the given function, including handling JSON de- and encoding and converting
   * caught exceptions to a serializable form. This method is meant to be used directly
   * by and for LLM function calling.
   */
  public function call(string $name, string $arguments, array $context= [], ?string $namespace= null): string {
    try {
      $result= $this->invoke($name, (array)Json::read($arguments), $context, $namespace);
    } catch (CannotInvoke $e) {
      $result= $this->error($e->getCause());
    } catch (Any $e) {
      $result= $this->error(Throwable::wrap($e));
    }

    return Json::of($result);
  }
}