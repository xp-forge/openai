<?php namespace com\openai\unittest;

use com\openai\tools\{Functions, Named, Namespaced};
use test\{Assert, Test, Values};

class FunctionsTest {
  use Implementations;

  /** Creates functions combined from named and namespaced definitions */
  private function combined($impl) {
    return new Functions(new Named($impl), new Namespaced(['greetings' => $impl]));
  }

  #[Test]
  public function can_create() {
    new Functions(new Named([]), new Namespaced([]));
  }

  #[Test, Values(from: 'implementations')]
  public function combined_schema($impl) {
    Assert::equals(
      [
        ...$this->tools(),
        [
          'type'        => 'namespace',
          'name'        => 'greetings',
          'description' => 'Greetings',
          'tools'       => $this->tools(),
        ],
      ],
      [...$this->combined($impl)->schema()]
    );
  }

  #[Test, Values(from: 'implementations')]
  public function call_global($impl) {
    Assert::equals('Hello World', $this->combined($impl)
      ->calls()
      ->invoke('hello', ['name' => 'World'], [], namespace: null)
    );
  }

  #[Test, Values(from: 'implementations')]
  public function call_namespaced($impl) {
    Assert::equals('Hello Namespace', $this->combined($impl)
      ->calls()
      ->invoke('hello', ['name' => 'Namespace'], [], namespace: 'greetings')
    );
  }

  #[Test]
  public function empty_string_representation() {
    Assert::equals(
      'com.openai.tools.Functions []',
      (new Functions())->toString()
    );
  }

  #[Test]
  public function string_representation() {
    Assert::equals(
      "com.openai.tools.Functions [\n".
      "  com.openai.tools.Named('Global functions') {}\n".
      "]",
      (new Functions(new Named([], 'Global functions')))->toString()
    );
  }
}