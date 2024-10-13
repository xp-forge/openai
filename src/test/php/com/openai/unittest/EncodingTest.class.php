<?php namespace com\openai\unittest;

use com\openai\Encoding;
use test\{Assert, Test, Values};

class EncodingTest {

  #[Test]
  public function named() {
    Assert::equals(Encoding::$cl100k_base, Encoding::named('cl100k_base'));
  }

  #[Test, Values(['gpt-4o', 'gpt-4o-2024-05-13', 'o1-preview', 'omni'])]
  public function for_omni_and_o1($model) {
    Assert::equals(Encoding::$o200k_base, Encoding::for($model));
  }

  #[Test, Values(['gpt-4', 'gpt-4-0314', 'gpt-3.5-turbo-0301', 'gpt-35'])]
  public function for_gpt35_and_40($model) {
    Assert::equals(Encoding::$cl100k_base, Encoding::for($model));
  }
}