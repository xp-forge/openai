<?php namespace com\openai\unittest;

use com\openai\{Encoding, TikTokenFilesIn};
use test\{Args, Assert, Test, Values};

#[Args('folder')]
class O200kBaseTest {
  private $encoder;

  /** Creates an instance with a given folder containing the `.tiktoken` files */
  public function __construct($folder= '.') {
    $this->encoder= Encoding::named('o200k_base')->load(new TikTokenFilesIn($folder));
  }

  /** @return iterable */
  private function fixtures() {
    yield ['hello world', [24912, 2375]];
    yield ['привет мир', [9501, 131903, 37934]];
    yield [".\n", [558]];
    yield ["today\n ", [58744, 198, 220]];
    yield ["today\n \n", [58744, 47812]];
    yield ["today\n  \n", [58744, 31835]];
    yield ['🌶', [64364, 114]];
    yield ["👍", [82514]];
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