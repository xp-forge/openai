<?php namespace com\openai\tools;

use Throwable as Any;
use lang\reflection\TargetException;
use lang\{Type, Nullable, Throwable, IllegalArgumentException};
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
    if (null === ($invokeable= $this->functions->invokeable($name, $namespace))) {
      throw new IllegalArgumentException("Unknown function '{$compound}'");
    }

    $pass= [];
    foreach ($invokeable->getParameters() as $param) {
      if ($attributes= $param->getAttributes(Context::class)) {
        $arguments= $attributes[0]->getArguments();
        $ptr= &$context;
        $select= $arguments['name'] ?? $arguments[0] ?? $param->name;
      } else {
        $ptr= &$named;
        $select= $param->name;
      }

      if (array_key_exists($select, $ptr)) {
        if (null === ($t= $param->getType())) {
          $type= Type::$VAR;
        } else if ($t->allowsNull()) {
          $type= new Nullable(Type::forName($t->getName()));
        } else {
          $type= Type::forName($t->getName());
        }
        $pass[$param->name]= $this->marshalling->unmarshal($ptr[$select], $type);
      } else if ($param->isOptional()) {
        $pass[$param->name]= $param->getDefaultValue();
      } else {
        throw new CannotInvoke("Missing argument '{$select}' for function '{$compound}'");
      }
    }

    try {
      return $this->marshalling->marshal($invokeable->invokeArgs($pass));
    } catch (Throwable $t) {
      throw new CannotInvoke("Exception while invoking function '{$compound}'", $t);
    }
  }

  /**
   * Call the given function, including handling JSON de- and encoding and converting
   * caught exceptions to a serializable form. This method is meant to be used directly
   * by and for LLM function calling.
   */
  public function call(string $name, string $arguments, array $context= [], ?string $namespace= null): string {
    try {
      $result= $this->invoke($name, Json::read($arguments), $context, $namespace);
    } catch (CannotInvoke $e) {
      $result= $this->error($e->getCause());
    } catch (Any $e) {
      $result= $this->error(Throwable::wrap($e));
    }

    return Json::of($result);
  }
}