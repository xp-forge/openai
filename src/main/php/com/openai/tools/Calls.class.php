<?php namespace com\openai\tools;

use Throwable as Any;
use lang\Throwable;
use lang\reflection\TargetException;

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
   * Invoke the function, including handling JSON de- and encoding
   *
   * @param  string $name
   * @param  string $arguments
   * @param  [:var] $context
   * @return string
   */
  public function invoke($name, $arguments, $context= []) {
    try {
      $return= $this->functions->invoke($name, json_decode($arguments, true), $context);
    } catch (TargetException $e) {
      $return= $this->error($e->getCause());
    } catch (Any $e) {
      $return= $this->error(Throwable::wrap($e));
    }

    return json_encode($return);
  }
}