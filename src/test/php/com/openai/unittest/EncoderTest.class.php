<?php namespace com\openai\unittest;

use com\openai\{Encoder, Encoding};
use test\{Assert, Before, Test};

class EncoderTest {
  const VOCABULARY= ['!' => 0, ' ' => 220, 'Test' => 2323, 'mm' => 3906, 'Ti' => 46451];

  private $encoding;

  #[Before]
  public function encoding() {
    $this->encoding= Encoding::named('cl100k_base');
  }

  #[Test]
  public function can_create() {
    new Encoder(self::VOCABULARY, $this->encoding);
  }

  #[Test]
  public function encode_single() {
    $encoder= new Encoder(self::VOCABULARY, $this->encoding);
    Assert::equals([0], [...$encoder->encode('!')]);
  }

  #[Test]
  public function split_along_word_boundaries() {
    $encoder= new Encoder(self::VOCABULARY, $this->encoding);
    Assert::equals([2323, 0], [...$encoder->encode('Test!')]);
  }

  #[Test]
  public function byte_pairs_merged() {
    $encoder= new Encoder(self::VOCABULARY, $this->encoding);
    Assert::equals([220, 46451, 3906], [...$encoder->encode(' Timm')]);
  }

  #[Test]
  public function count() {
    $encoder= new Encoder(self::VOCABULARY, $this->encoding);
    Assert::equals(4, $encoder->count('Test Timm'));
  }
}