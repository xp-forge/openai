<?php namespace com\openai\unittest;

use com\openai\tools\Namespaced;
use test\{Assert, Test, Values};

class NamespacedTest {
  use Implementations;

  #[Test]
  public function can_create() {
    new Namespaced();
  }

  #[Test]
  public function add() {
    $impl= new class() { };
    Assert::equals(
      new Namespaced(['greetings' => $impl]),
      (new Namespaced())->add('greetings', $impl),
    );
  }

  #[Test]
  public function description_from_impl_apidoc() {
    $functions= new Namespaced(['greetings' => /** Testing */ new class() { }]);
    Assert::equals('Testing', $functions->schema()->current()['description']);
  }

  #[Test, Values(from: 'implementations')]
  public function schema($impl) {
    $functions= new Namespaced(['greetings' => $impl]);
    Assert::equals(
      [
        [
          'type'        => 'namespace',
          'name'        => 'greetings',
          'description' => 'Greetings',
          'tools'       => $this->tools(),
        ],
      ],
      [...$functions->schema()]
    );
  }

  #[Test, Values(from: 'implementations')]
  public function call($impl) {
    Assert::equals('Hello Test', (new Namespaced(['greetings' => $impl]))
      ->calls()
      ->invoke('hello', ['name' => 'Test'], [], namespace: 'greetings')
    );
  }
}