<?php namespace com\openai\unittest;

use com\openai\v4a\Operation;
use test\{Assert, Test};

class OperationTest {

  #[Test]
  public function can_create() {
    new Operation('update', null, []);
  }

  #[Test]
  public function create_file() {
    $fixture= new Operation('add', null, [
      ['anchors' => [], 'end' => false, 'instructions' => [
        ['type' => 'add', 'value' => 'export const PORT = 8080'],
        ['type' => 'add', 'value' => 'export const HOST = "localhost"'],
      ]]
    ]);

    Assert::equals(
      ['export const PORT = 8080', 'export const HOST = "localhost"'],
      [...$fixture->apply('')]
    );
  }

  #[Test]
  public function change_sub_to_add() {
    $fixture= new Operation('add', null, [
      ['anchors' => [], 'end' => false, 'instructions' => [
        ['type' => 'keep',   'value' => 'function(a, b) {'],
        ['type' => 'delete', 'value' => '  return a - b'],
        ['type' => 'add',    'value' => '  return a + b'],
        ['type' => 'keep',   'value' => '}'],
      ]]
    ]);

    Assert::equals(
      ['function(a, b) {', '  return a + b', '}'],
      [...$fixture->apply("function(a, b) {\n  return a - b\n}")]
    );
  }

  #[Test]
  public function find_anchor() {
    $fixture= new Operation('add', null, [
      ['anchors' => ['Always'], 'end' => false, 'instructions' => [
        ['type' => 'keep',   'value' => '- Ask'],
        ['type' => 'delete', 'value' => '- Ask'],
        ['type' => 'add',    'value' => '- Help'],
      ]]
    ]);

    Assert::equals(
      ['# Instructions', '', 'Always do the following:', '- Ask', '- Help'],
      [...$fixture->apply(<<<'MARKDOWN'
        # Instructions

        Always do the following:
        - Ask
        - Ask
        MARKDOWN
      )]
    );
  }
}