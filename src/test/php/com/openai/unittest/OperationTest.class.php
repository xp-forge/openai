<?php namespace com\openai\unittest;

use com\openai\v4a\Operation;
use lang\IllegalStateException;
use test\{Assert, Expect, Test};

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
        ['type' => 'keep', 'value' => 'function(a, b) {'],
        ['type' => 'delete', 'value' => '  return a - b'],
        ['type' => 'add', 'value' => '  return a + b'],
        ['type' => 'keep', 'value' => '}'],
      ]]
    ]);

    Assert::equals(
      ['function(a, b) {', '  return a + b', '}'],
      [...$fixture->apply("function(a, b) {\n  return a - b\n}")]
    );
  }

  #[Test]
  public function find_anchor() {
    $fixture= new Operation('update', null, [
      ['anchors' => ['Always do the following:'], 'end' => false, 'instructions' => [
        ['type' => 'keep', 'value' => '- Ask'],
        ['type' => 'delete', 'value' => '- Ask'],
        ['type' => 'add', 'value' => '- Help'],
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

  #[Test]
  public function find_anchors() {
    $fixture= new Operation('update', null, [
      ['anchors' => ['class T {', '  create() {'], 'end' => false, 'instructions' => [
        ['type' => 'delete', 'value' => ''],
        ['type' => 'add', 'value' => '    console.log("Hello");'],
      ]]
    ]);

    Assert::equals(
      ['class T {', '  #name = "Test";', '', '  create() {', '    console.log("Hello");', '  }', '}'],
      [...$fixture->apply(<<<'JS'
        class T {
          #name = "Test";

          create() {

          }
        }
        JS
      )]
    );
  }

  #[Test]
  public function anchors_are_ordered_and_may_have_unrelated_lines_between_them() {
    $fixture= new Operation('update', null, [
      ['anchors' => ['first', 'second'], 'end' => false, 'instructions' => [
        ['type' => 'add', 'value' => 'added'],
      ]]
    ]);

    Assert::equals(
      ['first', 'unrelated', 'second', 'added'],
      [...$fixture->apply("first\nunrelated\nsecond")]
    );
  }

  #[Test]
  public function exact_context_is_preferred_over_an_earlier_trimmed_match() {
    $fixture= new Operation('update', null, [
      ['anchors' => [], 'end' => false, 'instructions' => [
        ['type' => 'delete', 'value' => 'line'],
        ['type' => 'add', 'value' => 'replacement'],
      ]]
    ]);

    Assert::equals(
      ['  line', 'replacement'],
      [...$fixture->apply("  line\nline")]
    );
  }

  #[Test]
  public function end_of_file_hunk_uses_last_context_match() {
    $fixture= new Operation('update', null, [
      ['anchors' => [], 'end' => true, 'instructions' => [
        ['type' => 'keep', 'value' => 'duplicate'],
        ['type' => 'add', 'value' => 'last'],
      ]]
    ]);

    Assert::equals(
      ['duplicate', 'middle', 'duplicate', 'last'],
      [...$fixture->apply("duplicate\nmiddle\nduplicate")]
    );
  }

  #[Test]
  public function eof_hunk_adds_after_current_eof_when_context_is_empty() {
    $fixture= new Operation('update', null, [
      ['anchors' => [], 'end' => true, 'instructions' => [
        ['type' => 'add', 'value' => 'last'],
      ]]
    ]);

    Assert::equals(['first', 'last'], [...$fixture->apply('first')]);
  }

  #[Test]
  public function find_empty_anchor() {
    $fixture= new Operation('update', null, [
      ['anchors' => [''], 'end' => false, 'instructions' => [
        ['type' => 'delete', 'value' => 'Old'],
        ['type' => 'add', 'value' => 'New'],
      ]]
    ]);

    Assert::equals(['New'], [...$fixture->apply('Old')]);
  }

  #[Test]
  public function yields_remaining_source_lines_once() {
    $fixture= new Operation('update', null, [
      ['anchors' => [], 'end' => false, 'instructions' => [
        ['type' => 'keep', 'value' => 'first'],
      ]]
    ]);

    Assert::equals(
      ['first', 'second', 'third'],
      [...$fixture->apply("first\nsecond\nthird")]
    );
  }

  #[Test, Expect(class: IllegalStateException::class, message: 'Cannot locate anchors [`Missing`]')]
  public function missing_anchor_stops_applying() {
    $fixture= new Operation('update', null, [
      ['anchors' => ['Missing'], 'end' => false, 'instructions' => [
        ['type' => 'add', 'value' => 'not added'],
      ]]
    ]);
    [...$fixture->apply("first\nsecond")];
  }

  #[Test, Expect(class: IllegalStateException::class, message: 'Cannot delete `expected`, have `actual`')]
  public function mismatched_delete_stops_applying() {
    $fixture= new Operation('update', null, [
      ['anchors' => [], 'end' => false, 'instructions' => [
        ['type' => 'delete', 'value' => 'expected'],
        ['type' => 'add', 'value' => 'not added'],
      ]]
    ]);
    [...$fixture->apply('actual')];
  }
}