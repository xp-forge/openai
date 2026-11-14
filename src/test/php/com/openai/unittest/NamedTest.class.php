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

  #[Test]
  public function empty_string_representation() {
    Assert::equals('com.openai.tools.Named {}', (new Named([]))->toString());
  }

  #[Test]
  public function string_representation() {
    $impl= [
      'print' => /** Prints a value */ fn($value) => true,
      'find'  => /** Finds files and folders */ fn($base, $pattern) => true,
      'test'  => /** Tests an expression */ fn($expr) => true,
    ];

    Assert::equals(
      "com.openai.tools.Named {\n".
      "  print(): Prints a value\n".
      "  find(): Finds files and folders\n".
      "  test(): Tests an expression\n".
      "}",
      (new Named($impl))->toString()
    );
  }
}