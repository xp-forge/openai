<?php namespace com\openai\unittest;

class Instantiation {
  public static $counter= 0;
  private $greeting;

  public function __construct($greeting= 'Hello') {
    $this->greeting= $greeting;
    self::$counter++;
  }

  /** Says hello */
  public function hello($name) {
    return "{$this->greeting} {$name}";
  }
}