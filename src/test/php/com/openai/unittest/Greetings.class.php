<?php namespace com\openai\unittest;

class Greetings {
  
  /** Says hello */
  public function hello($name) {
    return "Hello {$name}";
  }
}