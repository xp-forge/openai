<?php namespace com\openai;

/**
 * SimpleBytePairEncoding
 *
 * @see   https://github.com/openai/tiktoken/blob/main/tiktoken/_educational.py
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
   * @return string[]
   */
  private function merge($word) {
    $parts= str_split($word, 1);
    do {

      // Iterate over all pairs and find the pair we want to merge the most
      $l= sizeof($parts);
      $min= $pos= null;
      for ($i= 0; $i < $l - 1; $i++) {
        $rank= $this->vocabulary[$parts[$i].$parts[$i + 1]] ?? null;
        if (null !== $rank && (null === $min || $rank < $min)) {
          $min= $rank;
          $pos= $i;
        }
      }

      // If there were no pairs we could merge, we're done!
      if (null === $min) return $parts;

      // Otherwise, merge that pair and leave the rest unchanged. Then repeat.
      $parts= [...array_slice($parts, 0, $pos), $parts[$pos].$parts[$pos + 1], ...array_slice($parts, $pos + 2)];
    } while (true);
  }

  /** Encodes a given text, returning a list of tokens */
  public function encode(string $text): iterable {
    preg_match_all($this->pattern, $text, $words);
    foreach ($words[0] as $word) {
      if (isset($this->vocabulary[$word])) {
        yield $this->vocabulary[$word];
      } else {
        foreach ($this->merge($word) as $part) {
          yield $this->vocabulary[$part];
        }
      }
    }
  }

  /** Encodes a given text and returns number of tokens */
  public function count(string $text): int {
    preg_match_all($this->pattern, $text, $words);
    $c= 0;
    foreach ($words[0] as $word) {
      $c+= isset($this->vocabulary[$word]) ? 1 : sizeof($this->merge($word));
    }
    return $c;
  }
}