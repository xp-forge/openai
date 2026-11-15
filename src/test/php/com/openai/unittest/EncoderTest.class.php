<?php namespace com\openai\unittest;

use com\openai\{Encoder, Encoding};
use test\{Assert, Before, Test};

class EncoderTest {
  const VOCABULARY= [
    'a'    => 10,
    'b'    => 11,
    'c'    => 12,
    'ab'   => 4,
    'bc'   => 2,
    '1'    => 40,
    '2'    => 41,
    '12'   => 5,
    '!'    => 0,
    ' '    => 220,
    'mm'   => 3906,
    'Ti'   => 46451,
    'Test' => 2323,
  ];

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
  public function size_reflects_vocabulary() {
    Assert::equals(sizeof(self::VOCABULARY), (new Encoder(self::VOCABULARY, $this->encoding))->size());
  }

  #[Test]
  public function empty_input_produces_no_tokens() {
    $encoder= new Encoder(self::VOCABULARY, $this->encoding);
    Assert::equals([], [...$encoder->encode('')]);
    Assert::equals(0, $encoder->count(''));
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
  public function merges_lowest_ranked_pair_first() {
    Assert::equals([10, 2], [...(new Encoder(self::VOCABULARY, $this->encoding))->encode('abc')]);
  }

  #[Test]
  public function whole_word_token_takes_precedence() {
    Assert::equals([5], [...(new Encoder(self::VOCABULARY, $this->encoding))->encode('12')]);
  }

  #[Test]
  public function count() {
    $encoder= new Encoder(self::VOCABULARY, $this->encoding);
    Assert::equals(4, $encoder->count('Test Timm'));
  }
}