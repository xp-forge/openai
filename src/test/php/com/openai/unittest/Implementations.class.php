<?php namespace com\openai\unittest;

use com\openai\tools\Definition;

trait Implementations {

  /** @return iterable */
  private function implementations() {
    yield [new Greetings()];

    yield [['hello' => /** Says hello */ fn($name) => "Hello {$name}"]];

    yield [new class() {

      /** Says hello */
      public function hello($name) {
        return "Hello {$name}";
      }
    }];
  }

  private function tools($group= '') {
    $prefix= $group ? $group.'_' : '';
    return [
      new Definition($prefix.'hello', 'Says hello', [
        'type'       => 'object',
        'properties' => ['name' => ['type' => 'string', 'description' => 'Name']],
        'required'   => ['name'],
      ]),
    ];
  }
}