<?php namespace com\openai\unittest;

use com\openai\tools\Grouped;
use test\{Assert, Test, Values};

class GroupedTest {
  use Implementations;

  #[Test]
  public function can_create() {
    new Grouped();
  }

  #[Test]
  public function add() {
    $impl= new class() { };
    Assert::equals(
      new Grouped(['greetings' => $impl]),
      (new Grouped())->add('greetings', $impl),
    );
  }

  #[Test, Values(from: 'implementations')]
  public function schema($impl) {
    $functions= new Grouped(['greetings' => $impl]);
    Assert::equals($this->tools('greetings'), [...$functions->schema()]);
  }

  #[Test, Values(from: 'implementations')]
  public function call($impl) {
    Assert::equals('Hello Test', (new Grouped(['greetings' => $impl]))
      ->calls()
      ->invoke('greetings_hello', ['name' => 'Test'], [], namespace: null)
    );
  }
}