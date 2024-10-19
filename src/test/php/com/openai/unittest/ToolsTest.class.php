<?php namespace com\openai\unittest;

use com\openai\Tools;
use com\openai\tools\Functions;
use test\{Assert, Test};

class ToolsTest {

  #[Test]
  public function can_create() {
    new Tools();
  }

  #[Test]
  public function code_interpreter() {
    Assert::equals(
      [['type' => 'code_interpreter']],
      (new Tools('code_interpreter'))->selection
    );
  }

  #[Test]
  public function with_custom_functions() {
    $functions= (new Functions())->register('greet', new class() {
      public function world() { return 'Hello World!'; }
    });

    Assert::equals(
      [[
        'type' => 'function',
        'function' => [
          'name'        => 'greet_world',
          'description' => 'World',
          'parameters'  => $functions->schema()->current()['input'],
        ],
      ]],
      (new Tools($functions))->selection
    );
  }
}