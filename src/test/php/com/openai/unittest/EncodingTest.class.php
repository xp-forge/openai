<?php namespace com\openai\unittest;

use com\openai\Encoding;
use test\{Assert, Test, Values};

class EncodingTest {

  #[Test]
  public function cl100k_base() {
    Assert::equals(Encoding::$cl100k_base, Encoding::named('cl100k_base'));
  }

  #[Test]
  public function o200k_base() {
    Assert::equals(Encoding::$o200k_base, Encoding::named('o200k_base'));
  }

  #[Test]
  public function o200k_harmony() {
    Assert::equals(Encoding::$o200k_harmony, Encoding::named('o200k_harmony'));
  }
}