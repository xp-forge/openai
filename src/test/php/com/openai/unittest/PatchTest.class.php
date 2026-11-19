<?php namespace com\openai\unittest;

use com\openai\v4a\{Patch, Operation};
use lang\FormatException;
use test\{Assert, Expect, Test, Values};

class PatchTest {

  #[Test]
  public function can_create() {
    new Patch([]);
  }

  #[Test]
  public function simple_operations() {
    Assert::equals(
      [new Operation('update', null, [
        ['anchors' => [], 'end' => false, 'instructions' => [
          ['type' => 'keep', 'value' => 'Hello'],
          ['type' => 'delete', 'value' => 'world'],
          ['type' => 'add', 'value' => 'everyone'],
        ]],
      ])],
      Patch::operations(" Hello\n-world\n+everyone")
    );
  }

  #[Test]
  public function bare_lines_are_empty_context() {
    Assert::equals(
      [new Operation('update', null, [
        ['anchors' => [], 'end' => false, 'instructions' => [
          ['type' => 'delete', 'value' => 'first'],
          ['type' => 'keep', 'value' => ''],
          ['type' => 'add', 'value' => 'second'],
        ],],
      ])],
      Patch::operations("-first\n\n+second")
    );
  }

  #[Test, Values([
    ["@@ Hello\n+new", ['Hello']],
    ["@@ class T\n@@ function new\n+new", ['class T', 'function new']],
  ])]
  public function anchored($input, $expected) {
    Assert::equals(
      [new Operation('update', null, [
        ['anchors' => $expected, 'end' => false, 'instructions' => [
          ['type' => 'add', 'value' => 'new'],
        ]],
      ])],
      Patch::operations($input)
    );
  }

  #[Test]
  public function hunk_after_end_of_file() {
    Assert::equals(
      [new Operation('update', null, [
        ['anchors' => ['last'], 'end' => true, 'instructions' => [
          ['type' => 'delete', 'value' => 'old'],
          ['type' => 'add', 'value' => 'new'],
        ]],
        ['anchors' => ['next'], 'end' => false, 'instructions' => [
          ['type' => 'delete', 'value' => 'before'],
          ['type' => 'add', 'value' => 'after'],
        ]],
      ])],
      Patch::operations("@@ last\n-old\n+new\n*** End of File\n\n@@ next\n-before\n+after")
    );
  }

  #[Test]
  public function envelope() {
    $patch= new Patch(<<<'DIFF'
      *** Begin Patch
      *** Update File: example.txt
       Hello
      -world
      +everyone
      *** End Patch
      DIFF
    );
    Assert::equals(
      [new Operation('update', 'example.txt', [
        ['anchors' => [], 'end' => false, 'instructions' => [
          ['type' => 'keep', 'value' => 'Hello'],
          ['type' => 'delete', 'value' => 'world'],
          ['type' => 'add', 'value' => 'everyone'],
        ]],
      ])],
      $patch->operations
    );
  }

  #[Test]
  public function multi_file() {
    $patch= new Patch(<<<'DIFF'
      *** Begin Patch
      *** Delete File: example.txt
      *** Add File: test.txt
      +Test
      *** End Patch
      DIFF
    );
    Assert::equals(
      [new Operation('delete', 'example.txt'), new Operation('add', 'test.txt', [
        ['anchors' => [], 'end' => false, 'instructions' => [
          ['type' => 'add', 'value' => 'Test'],
        ]],
      ])],
      $patch->operations
    );
  }

  #[Test]
  public function move_to() {
    $patch= new Patch(<<<'DIFF'
      *** Begin Patch
      *** Update File: src/old.txt
      *** Move to: src/new.txt
      @@
      -Hello, world!
      +Hello, everyone!
      *** End Patch
      DIFF
    );
    Assert::equals(
      [new Operation('update', ['src/old.txt' => 'src/new.txt'], [
        ['anchors' => [''], 'end' => false, 'instructions' => [
          ['type' => 'delete', 'value' => 'Hello, world!'],
          ['type' => 'add', 'value' => 'Hello, everyone!'],
        ]],
      ])],
      $patch->operations
    );
  }

  #[Test]
  public function end_of_file() {
    $patch= new Patch(<<<'DIFF'
      *** Begin Patch
      *** Update File: example.txt
      @@
       end
      +new last line
      *** End of File
      *** End Patch
      DIFF
    );
    Assert::equals(
      [new Operation('update', 'example.txt', [
        ['anchors' => [''], 'end' => true, 'instructions' => [
          ['type' => 'keep', 'value' => 'end'],
          ['type' => 'add', 'value' => 'new last line'],
        ]],
      ])],
      $patch->operations
    );
  }

  #[Test]
  public function stacked_anchors_and_multiple_hunks() {
    $patch= new Patch(<<<'DIFF'
      *** Begin Patch
      *** Update File: example.txt
      @@ class Example
      @@   function run() {
      -old
      +new
      @@ class Other
       context
      +added
      *** End of File
      *** Update File: other.txt
      -before
      +after
      *** End Patch
      DIFF
    );
    Assert::equals(
      [
        new Operation('update', 'example.txt', [
          ['anchors' => ['class Example', '  function run() {'], 'end' => false, 'instructions' => [
            ['type' => 'delete', 'value' => 'old'],
            ['type' => 'add', 'value' => 'new'],
          ]],
          ['anchors' => ['class Other'], 'end' => true, 'instructions' => [
            ['type' => 'keep', 'value' => 'context'],
            ['type' => 'add', 'value' => 'added'],
          ]],
        ]),
        new Operation('update', 'other.txt', [
          ['anchors' => [], 'end' => false, 'instructions' => [
            ['type' => 'delete', 'value' => 'before'],
            ['type' => 'add', 'value' => 'after'],
          ]],
        ]),
      ],
      $patch->operations
    );
  }

  #[Test, Expect(class: FormatException::class, message: 'Unexpected `+extra`')]
  public function instructions_after_end_of_file_require_hunk_header() {
    new Patch("@@\n+last\n*** End of File\n+extra");
  }

  #[Test, Expect(class: FormatException::class, message: '`End of File` without instructions')]
  public function end_of_file_requires_instructions() {
    new Patch("@@\n*** End of File");
  }

  #[Test, Expect(class: FormatException::class, message: 'Invalid line `invalid`')]
  public function invalid_line() {
    new Patch('invalid');
  }

  #[Test, Expect(class: FormatException::class, message: 'Unexpected end of file')]
  public function unclosed_enveloppe() {
    new Patch('*** Begin Patch');
  }

  #[Test, Expect(class: FormatException::class, message: 'Unknown operation `Upload file`')]
  public function unknown_command() {
    new Patch("*** Begin Patch\n*** Upload file: local.txt");
  }

  #[Test, Expect(class: FormatException::class, message: '`Move to` only allowed for update operations')]
  public function move_to_not_allowed_with_add() {
    new Patch("*** Begin Patch\n*** Add file: test.txt\n*** Move to: /dev/null\n");
  }
}
