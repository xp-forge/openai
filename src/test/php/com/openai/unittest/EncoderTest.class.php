<?php namespace com\openai\unittest;

use com\openai\{Encoder, Encoding};
use lang\{IllegalArgumentException, IllegalStateException};
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
  #[Test]
  public function merges_across_long_word_boundary() {
    $encoder= new Encoder(self::VOCABULARY, $this->encoding);
    $text= str_repeat('a', 99).'bc';
    Assert::equals([...array_fill(0, 99, 10), 2], [...$encoder->encode($text)]);
    Assert::equals(100, $encoder->count($text));
  }

  #[Test]
  public function tied_pairs_merge_from_the_left() {
    $encoder= new Encoder(['a' => 10, 'aa' => 1], $this->encoding);
    Assert::equals([1, 10], [...$encoder->encode('aaa')]);
  }

  #[Test]
  public function newly_formed_pairs_take_priority() {
    $encoder= new Encoder(
      ['a' => 10, 'b' => 11, 'c' => 12, 'd' => 13, 'ab' => 2, 'bc' => 3, 'cd' => 4, 'abc' => 1],
      $this->encoding
    );
    Assert::equals([1, 13], [...$encoder->encode('abcd')]);
  }

  #[Test]
  public function malformed_utf8_is_rejected() {
    $encoder= new Encoder(self::VOCABULARY, $this->encoding);
    Assert::throws(IllegalArgumentException::class, fn() => [...$encoder->encode("a\xffb")]);
    Assert::throws(IllegalArgumentException::class, fn() => $encoder->count("a\xffb"));
  }

  #[Test]
  public function missing_vocabulary_bytes_are_rejected() {
    $encoder= new Encoder(self::VOCABULARY, $this->encoding);
    Assert::throws(IllegalStateException::class, fn() => [...$encoder->encode('az')]);
    Assert::throws(IllegalStateException::class, fn() => $encoder->count('az'));
  }

}