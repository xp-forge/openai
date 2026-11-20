<?php namespace com\openai\v4a;

use io\streams\StringReader;
use lang\IllegalStateException;

/** @test com.openai.unittest.OperationTest */  
class Operation {
  public $type, $path, $hunks;
  public $target= null;

  /**
   * Creates a new operation
   *
   * @param  ?string|[:string] $path
   */
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
   * @param  string[]|string|io.streams.InputStream|io.Channel $source
   * @return string[]
   * @throws lang.IllegalStateException when errors occur
   */
  public function apply($source): array {
    if (is_array($source)) {
      $lines= $source;
    } else {
      $lines= [];
      $reader= new StringReader($source);
      while (null !== ($line= $reader->readLine())) {
        $lines[]= $line;
      }
    }

    $result= [];
    $cursor= 0;
    foreach ($this->hunks as $hunk) {

      // First, find anchors; empty anchor strings match immediately
      foreach ($hunk['anchors'] as $anchor) {
        if ('' === $anchor) continue;

        $found= $this->find([['value' => $anchor]], $lines, $cursor);
        if (null === $found) {
          throw new IllegalStateException('Cannot locate anchors [`'.implode('`, `', $hunk['anchors']).'`]');
        }
        while ($cursor <= $found) $result[]= $lines[$cursor++];
      }

      // Match keep/delete instructions as a contiguous context. Additions
      // don't consume source lines and therefore aren't part of that context.
      $context= [];
      foreach ($hunk['instructions'] as $instruction) {
        if ('add' !== $instruction['type']) $context[]= $instruction;
      }

      $start= $this->find($context, $lines, $cursor, $hunk['end']);
      if (null === $start) {
        if ($context) {
          $line= $lines[$cursor] ?? '<end of file>';
          throw new IllegalStateException("Cannot {$context[0]['type']} `{$context[0]['value']}`, have `{$line}`");
        }

        $start= $hunk['end'] ? sizeof($lines) : $cursor;
      }

      // Apply hunks
      while ($cursor < $start) $result[]= $lines[$cursor++];
      foreach ($hunk['instructions'] as $instruction) {
        switch ($instruction['type']) {
          case 'add': $result[]= $instruction['value']; break;
          case 'keep': $result[]= $lines[$cursor++]; break;
          case 'delete': $cursor++; break;
          default: throw new IllegalStateException("Unexpected instruction `{$instruction['type']}`");
        }
      }
    }

    // Yield the source which remains after the final hunk.
    while ($cursor < sizeof($lines)) {
      $result[]= $lines[$cursor++];
    }
    return $result;
  }

  /**
   * Finds a contiguous context, preferring exact, trailing-whitespace and
   * finally fully trimmed matches. EOF hunks first try the matching context
   * at the end of the source.
   *
   * @return ?int
   */
  private function find(array $context, array $lines, int $from, bool $end= false) {
    if (empty($context)) return $end ? sizeof($lines) : $from;

    $last= sizeof($lines) - sizeof($context);
    if ($end && $last < $from) return null;

    $passes= [
      fn($source, $expected) => $source === $expected,
      fn($source, $expected) => rtrim($source) === rtrim($expected),
      fn($source, $expected) => trim($source) === trim($expected),
    ];
    foreach ($passes as $compare) {
      for ($start= $end ? $last : $from, $limit= $last; $start <= $limit; $start++) {
        $matches= true;
        foreach ($context as $offset => $instruction) {
          if (!$compare($lines[$start + $offset], $instruction['value'])) {
            $matches= false;
            break;
          }
        }
        if ($matches) return $start;
      }
      if ($end) break;
    }
    return null;
  }
}