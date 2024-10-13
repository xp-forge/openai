<?php namespace com\openai;

use io\{File, Folder};
use lang\IllegalArgumentException;

/**
 * Loads tokens from `.tiktoken` files
 *
 * @see   https://openaipublic.blob.core.windows.net/encodings/r50k_base.tiktoken - Ada, Curie, Babbage
 * @see   https://openaipublic.blob.core.windows.net/encodings/p50k_base.tiktoken - Curie, Code
 * @see   https://openaipublic.blob.core.windows.net/encodings/cl100k_base.tiktoken - GPT 3.5 / 4.0 
 * @see   https://openaipublic.blob.core.windows.net/encodings/o200k_base.tiktoken - o1, Omni
 * @test  com.openai.unittest.FromTikTokenTest
 */
class TikTokenFilesIn extends Source {
  private $folder;

  /**
   * Creates new tiktoken file source
   * 
   * @param  io.Folder $folder
   */
  public function __construct($folder) {
    $this->folder= $folder instanceof Folder ? $folder : new Folder($folder);
  }

  /** Returns the folder the files will be loaded from */
  public function folder(): Folder { return $this->folder; }

  /**
   * Yields the tokens and their associated ranks from the given source
   *
   * @param  string $source
   * @throws lang.IllegalArgumentException
   * @throws io.IOException
   */
  public function tokens($source): iterable {
    $tried= [];
    foreach ([$source, $source.'.tiktoken'] as $name) {
      $file= new File($this->folder, $name);
      if ($file->exists()) {
        $file->open(File::READ);
        try {
          while (false !== ($line= $file->gets(2048))) {
            sscanf($line, '%s %d', $encoded, $rank);
            yield base64_decode($encoded) => $rank;
          }
        } finally {
          $file->close();
        }
        return;
      }
      $tried[]= $file->getURI();
    }

    throw new IllegalArgumentException('Source does not exist, tried ['.implode(', ', $tried).']');
  }
}
