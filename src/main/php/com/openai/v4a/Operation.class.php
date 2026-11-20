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

    // Search forwards until all anchors have been found, trying equality
    // as well as whitespace-trimmed equality.
    $search= function($search) use($lines) {
      while ($lines->valid()) {
        $line= $lines->current();
        if ($line === $search || trim($line) === trim($search)) return;

        yield $line;
        $lines->next();
      }
    };

    foreach ($this->hunks as $hunk) {
      foreach ($hunk['anchors'] as $anchor) {
        if ('' === $anchor) continue;

        yield from $search($anchor);

        if (!$lines->valid()) {
          throw new IllegalStateException('Cannot locate anchors [`'.implode('`, `', $hunk['anchors']).'`]');
        }

        // Yield the anchor line itself
        yield $lines->current();
        $lines->next();
      }

      // Apply instructions
      foreach ($hunk['instructions'] as $instruction) {
        $line= $lines->current();
        switch ($instruction['type']) {
          case 'add':
            yield $instruction['value'];
            break;

          case 'delete':
            yield from $search($instruction['value']);
            if (!$lines->valid()) {
              throw new IllegalStateException("Cannot delete `{$instruction['value']}`, have `{$line}`");
            }

            $lines->next();
            break;

          case 'keep':
            yield from $search($instruction['value']);
            if (!$lines->valid()) {
              throw new IllegalStateException("Cannot keep `{$instruction['value']}`, have `{$line}`");
            }

            yield $lines->current();
            $lines->next();
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