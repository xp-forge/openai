<?php namespace com\openai\unittest;

trait TestVocabulary {
  private $file;

  #[Before]
  public function file() {
    $this->file= (new TempFile())->containing(
      "IQ== 0\n".
      "VGVzdA== 2323\n".
      "bW0= 3906\n".
      "VGk= 46451\n"
    );
  }
