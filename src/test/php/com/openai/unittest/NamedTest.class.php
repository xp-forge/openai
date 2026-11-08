<?php namespace com\openai\unittest;

use com\openai\tools\Named;
use test\{Assert, Test, Values};

class NamedTest {
  use Implementations;

  #[Test]
  public function can_create() {
    new Named([]);
  }

  #[Test]
  public function description() {
    Assert::null((new Named([]))->description);
    Assert::equals('Testing', (new Named([], description: 'Testing'))->description);
  }

  #[Test]
  public function description_from_impl_apidoc() {
    Assert::equals('Testing', (new Named(/** Testing */ new class() { }))->description);
  }

  #[Test, Values(from: 'implementations')]
  public function schema($impl) {
    Assert::equals($this->tools(), [...(new Named($impl))->schema()]);
  }

  #[Test, Values(from: 'implementations')]
  public function call($impl) {
    Assert::equals('Hello Test', (new Named($impl))
      ->calls()
      ->invoke('hello', ['name' => 'Test'], [], namespace: null)
    );
  }
}