<?php namespace com\openai\unittest;

use com\openai\tools\{Functions, Calls};
use lang\IllegalAccessException;
use test\{Assert, Before, Test};

class CallsTest {
  private $functions;

  #[Before]
  public function functions() {
    $this->functions= (new Functions())->register('testing', new class() {

      /** Greets the user */
      public function greet($name) {
        if (empty($name)) {
          throw new IllegalAccessException('Name may not be empty!');
        }

        return "Hello {$name}";
      }
    });
  }

  #[Test]
  public function can_create() {
    new Calls($this->functions);
  }

  #[Test]
  public function invoke_successfully() {
    Assert::equals(
      '"Hello World"',
      (new Calls($this->functions))->invoke('testing_greet', '{"name":"World"}')
    );    
  }

  #[Test]
  public function missing_argument() {
    Assert::equals(
      '{"error":"lang.IllegalArgumentException","message":"Missing argument name for testing_greet"}',
      (new Calls($this->functions))->invoke('testing_greet', '{}')
    );    
  }

  #[Test]
  public function target_error() {
    Assert::equals(
      '{"error":"lang.IllegalAccessException","message":"Name may not be empty!"}',
      (new Calls($this->functions))->invoke('testing_greet', '{"name":""}')
    );    
  }

  #[Test]
  public function catching_error() {
    $caught= null;
    (new Calls($this->functions))
      ->catching(function($t) use(&$caught) { $caught= $t; })
      ->invoke('testing_greet', '{"name":""}')
    ;

    Assert::instance(IllegalAccessException::class, $caught);    
  }

  #[Test]
  public function modifying_error() {
    $result= (new Calls($this->functions))
      ->catching(fn($t) => ['error' => $t->getMessage()])
      ->invoke('testing_greet', '{"name":""}')
    ;

    Assert::equals('{"error":"Name may not be empty!"}', $result);
  }
}