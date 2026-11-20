<?php namespace com\openai\v4a;

use io\streams\LinesIn;
use lang\IllegalStateException;

/** @test com.openai.unittest.OperationTest */  
class Operation {
  public $type, $path, $hunks;
  public $target= null;

  public function __construct(string $type, $path, array $hunks= []) {
    $this->type= $type;
    if (is_array($path)) {
      $this->path= key($path);
      $this->target= current($path);
    } else {
      $this->path= $path;
    }
    $this->hunks= $hunks;
  }

  /**
   * Applies hunks in this operation to the given source
   *
   * @param  string
   */
  public function apply($source) {
    $lines= $source instanceof LinesIn ? $source->getIterator() : (new LinesIn($source))->getIterator();

    foreach ($this->hunks as $hunk) {

      // Search forwards until all anchors have been found
      foreach ($hunk['anchors'] as $anchor) {
        while ($lines->valid()) {
          $line= $lines->current();
          yield $line;

          $lines->next();
          if (0 === strncmp($line, $anchor, strlen($anchor))) continue 2;
        }

        throw new IllegalStateException('Cannot locate anchors [`'.implode('`, `', $hunk['anchors']).'`]');
      }

      // Apply instructions
      apply: foreach ($hunk['instructions'] as $instruction) {
        $line= $lines->current();
        switch ($instruction['type']) {
          case 'add':
            yield $instruction['value'];
            break;

          case 'delete':
            if ($line !== $instruction['value']) {
              throw new IllegalStateException("Cannot delete `{$instruction['value']}` at line {$lines->key()}, have `{$line}`");
            }

            $lines->next();
            break;

          case 'keep':
            if ($line !== $instruction['value']) {
              throw new IllegalStateException("Cannot keep `{$instruction['value']}` at line {$lines->key()}, have `{$line}`");
              return;
            }

            $lines->next();
            yield $line;
            break;
        }
      }
    }

    // Yield rest of file
    while ($lines->valid()) {
      yield $lines->current();
      $lines->next();
    }
  }
}