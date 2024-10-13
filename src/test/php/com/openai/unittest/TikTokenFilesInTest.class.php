<?php namespace com\openai\unittest;

use com\openai\TikTokenFilesIn;
use io\{Folder, TempFile};
use lang\IllegalArgumentException;
use test\{Assert, Before, Expect, Test};

class TikTokenFilesInTest {
  private $file;

  #[Before]
  public function file() {
    $this->file= (new TempFile())->containing(
      "IQ== 0\n".
      "IA== 220\n".
      "VGVzdA== 2323\n".
      "bW0= 3906\n".
      "VGk= 46451\n"
    );
  }

  #[Test]
  public function can_create() {
    new TikTokenFilesIn($this->file->path);
  }

  #[Test, Expect(class: IllegalArgumentException::class, message: '/tried \[.+does-not-exist, .+does-not-exist.tiktoken\]/')]
  public function tries_file_as_given_and_with_tiktoken_extension() {
    iterator_to_array((new TikTokenFilesIn('.'))->tokens('does-not-exist'));
  }

  #[Test]
  public function folder_accessor() {
    Assert::equals(new Folder($this->file->path), (new TikTokenFilesIn($this->file->path))->folder());
  }

  #[Test]
  public function tokens() {
    Assert::equals(
      ['!' => 0, ' ' => 220, 'Test' => 2323, 'mm' => 3906, 'Ti' => 46451],
      iterator_to_array((new TikTokenFilesIn($this->file->path))->tokens($this->file->filename))
    );
  }
}