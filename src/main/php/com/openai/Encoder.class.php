<?php namespace com\openai;

use SplPriorityQueue;
use lang\{IllegalArgumentException, IllegalStateException};

/**
 * SimpleBytePairEncoding
 *
 * @see   https://github.com/openai/tiktoken/blob/main/tiktoken/_educational.py
 * @see   https://github.com/openai/tiktoken/blob/main/src/lib.rs
 * @test  com.openai.unittest.EncoderTest
 * @todo  Implement special characters
 */
class Encoder {
  private $vocabulary= [];
  private $pattern;

  /** Creates a new encoder from a given vocabulary and pattern to split the text on */
  public function __construct(iterable $vocabulary, Encoding $encoding) {
    foreach ($vocabulary as $token => $rank) {
      $this->vocabulary[$token]= $rank;
    }
    $this->pattern= $encoding->pattern();
  }

  /** Returns vocabulary size */
  public function size(): int { return sizeof($this->vocabulary); }

  /**
   * Merge byte pairs in a given word
   *
   * @param  string $word
   * @return iterable
   */
  private function merge($word) {
    $parts= str_split($word);
    $size= sizeof($parts);
    $previous= $next= $versions= [];
    for ($i= 0; $i < $size; $i++) {
      $previous[$i]= $i - 1;
      $next[$i]= $i + 1 < $size ? $i + 1 : -1;
      $versions[$i]= 0;
    }

    // Queue adjacent pairs by rank, with the leftmost pair winning ties.
    // Versions discard candidates invalidated by a previous merge.
    $queue= new SplPriorityQueue();
    $add= function($left) use (&$parts, &$next, &$versions, $queue) {
      if ($left < 0 || -1 === ($right= $next[$left])) return;
      $rank= $this->vocabulary[$parts[$left].$parts[$right]] ?? null;
      if (null !== $rank) {
        $queue->insert([$left, $right, $versions[$left], $versions[$right]], [-$rank, -$left]);
      }
    };
    for ($i= 0; $i < $size - 1; $i++) $add($i);

    while (!$queue->isEmpty()) {
      [$left, $right, $leftVersion, $rightVersion]= $queue->extract();
      if ($next[$left] !== $right || $versions[$left] !== $leftVersion || $versions[$right] !== $rightVersion) continue;

      $parts[$left].= $parts[$right];
      $versions[$left]++;
      $next[$left]= $next[$right];
      if (-1 !== $next[$right]) $previous[$next[$right]]= $left;
      $add($previous[$left]);
      $add($left);
    }

    for ($i= 0; $i < $size; $i= $next[$i]) {
      yield $parts[$i];
      if (-1 === $next[$i]) break;
    }
  }

  /** Encodes a given text, returning a list of tokens */
  public function encode(string $text): iterable {
    if (false === preg_match_all($this->pattern, $text, $words)) {
      $e= new IllegalArgumentException('Cannot split text');
      \xp::gc(__FILE__);
      throw $e;
    }

    foreach ($words[0] as $word) {
      if ($token= $this->vocabulary[$word] ?? null) {
        yield $token;
      } else {
        foreach ($this->merge($word) as $part) {
          yield $this->vocabulary[$part] ?? throw new IllegalStateException('Unknown token '.bin2hex($part));
        }
      }
    }
  }

  /** Encodes a given text and returns number of tokens */
  public function count(string $text): int {
    return iterator_count($this->encode($text));
  }
}