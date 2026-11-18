<?php namespace com\openai\unittest;

use com\openai\V4ADiff;
use test\{Assert, Test, Values};

class V4ADiffTest {

  /** Returns result as outcome */
  private function outcome(iterable $results): array {
    $outcome= [true => [], false => []];;
    foreach ($results as $success => $out) {
      $outcome[$success][]= $out;
    }
    return $outcome;
  }

  #[Test]
  public function can_create() {
    new V4ADiff([]);
  }

  #[Test]
  public function create() {
    $diff= new V4ADiff([
      ['type' => 'add', 'value' => 'export const PORT = 8080'],
      ['type' => 'add', 'value' => 'export const HOST = "localhost"'],
    ]);
    $outcome= $this->outcome($diff->apply(''));

    Assert::equals([], $outcome[false]);
    Assert::equals(['export const PORT = 8080', 'export const HOST = "localhost"'], $outcome[true]);
  }

  #[Test]
  public function change_sub_to_add() {
    $diff= new V4ADiff([
      ['type' => 'keep',   'value' => 'function(a, b) {'],
      ['type' => 'delete', 'value' => '  return a - b'],
      ['type' => 'add',    'value' => '  return a + b'],
      ['type' => 'keep',   'value' => '}'],
    ]);
    $outcome= $this->outcome($diff->apply("function(a, b) {\n  return a - b\n}"));

    Assert::equals([], $outcome[false]);
    Assert::equals(['function(a, b) {', '  return a + b', '}'], $outcome[true]);
  }

  #[Test]
  public function find_anchor() {
    $diff= new V4ADiff([
      ['type' => 'anchor', 'value' => 'Always'],
      ['type' => 'keep',   'value' => '- Ask'],
      ['type' => 'delete', 'value' => '- Ask'],
      ['type' => 'add',    'value' => '- Help'],
    ]);
    $outcome= $this->outcome($diff->apply(<<<'MARKDOWN'
      # Instructions

      Always do the following:
      - Ask
      - Ask
      MARKDOWN
    ));

    Assert::equals([], $outcome[false]);
    Assert::equals(['# Instructions', '', 'Always do the following:', '- Ask', '- Help'], $outcome[true]);
  }

  #[Test]
  public function missing_anchor_stops_applying() {
    $diff= new V4ADiff([
      ['type' => 'anchor', 'value' => 'Missing'],
      ['type' => 'add',    'value' => 'not added'],
    ]);
    $outcome= $this->outcome($diff->apply("first\nsecond"));

    Assert::equals(['first', 'second'], $outcome[true]);
    Assert::equals(1, sizeof($outcome[false]));
  }

  #[Test]
  public function mismatched_delete_stops_applying() {
    $diff= new V4ADiff([
      ['type' => 'delete', 'value' => 'expected'],
      ['type' => 'add',    'value' => 'not added'],
    ]);
    $outcome= $this->outcome($diff->apply('actual'));

    Assert::equals([], $outcome[true]);
    Assert::equals(['Cannot delete `expected` at line 1, have `actual`'], $outcome[false]);
  }

  #[Test]
  public function yields_remaining_source_lines_once() {
    $diff= new V4ADiff([
      ['type' => 'keep', 'value' => 'first'],
    ]);
    $outcome= $this->outcome($diff->apply("first\nsecond\nthird"));

    Assert::equals([], $outcome[false]);
    Assert::equals(['first', 'second', 'third'], $outcome[true]);
  }

  #[Test]
  public function parse_edits() {
    Assert::equals(
      [
        ['type' => 'add',    'value' => 'first'],
        ['type' => 'delete', 'value' => 'second'],
        ['type' => 'keep',   'value' => 'third'],
      ],
      (new V4ADiff("+first\n-second\n third"))->instructions
    );
  }

  #[Test]
  public function parse_begin_anchor() {
    Assert::equals(
      [
        ['type' => 'anchor', 'value' => ''],
        ['type' => 'add',    'value' => '  return a + b'],
      ],
      (new V4ADiff("@@\n+  return a + b"))->instructions
    );
  }

  #[Test]
  public function parse_anchor() {
    Assert::equals(
      [
        ['type' => 'anchor', 'value' => 'function(a, b)'],
        ['type' => 'add',    'value' => '  return a + b'],
      ],
      (new V4ADiff("@@ function(a, b)\n+  return a + b"))->instructions
    );
  }
}
