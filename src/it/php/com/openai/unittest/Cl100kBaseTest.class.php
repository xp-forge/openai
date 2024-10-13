<?php namespace com\openai\unittest;

use com\openai\{Encoding, TikTokenFilesIn};
use test\{Args, Assert, Test, Values};

#[Args('folder')]
class Cl100kBaseTest {
  private $encoder;

  /** Creates an instance with a given folder containing the `.tiktoken` files */
  public function __construct($folder= '.') {
    $this->encoder= Encoding::named('cl100k_base')->load(new TikTokenFilesIn($folder));
  }

  /** @return iterable */
  private function fixtures() {
    yield ['hello world', [15339, 1917]];
    yield ['привет мир', [8164, 2233, 28089, 8341, 11562, 78746]];
    yield [".\n", [627]];
    yield ["today\n ", [31213, 198, 220]];
    yield ["today\n \n", [31213, 27907]];
    yield ["today\n  \n", [31213, 14211]];
    yield ['🌶', [9468, 234, 114]];
    yield ["👍", [9468, 239, 235]];
  }

  #[Test]
  public function empty() {
    Assert::equals([], [...$this->encoder->encode('')]);
  }

  #[Test, Values(from: 'fixtures')]
  public function encode($text, $expected) {
    Assert::equals($expected, [...$this->encoder->encode($text)]);
  }
}