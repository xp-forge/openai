<?php namespace com\openai;

use lang\{Enum, IllegalArgumentException};

/**
 * Encoding enumeration, supporting `r50k_base`, `p50k_base`, `cl100k_base` and
 * `o200k_base`.
 * 
 * @see   https://github.com/openai/tiktoken/blob/main/tiktoken_ext/openai_public.py
 * @test  com.openai.unittest.EncodingTest
 */
class Encoding extends Enum {
  public static $r50k_base, $p50k_base, $cl100k_base, $o200k_base, $o200k_harmony;
  private static $definitions= [
    'r50k_base'   => [
      'file'    => 'r50k_base',
      'pattern' => '/\'(?:[sdmt]|ll|ve|re)| ?\p{L}++| ?\p{N}++| ?[^\s\p{L}\p{N}]++|\s++$|\s+(?!\S)|\s/u',
      'special' => ['<|endoftext|>' => 50256],
    ],
    'p50k_base'   => [
      'file'    => 'p50k_base',
      'pattern' => '/\'(?:[sdmt]|ll|ve|re)| ?\p{L}++| ?\p{N}++| ?[^\s\p{L}\p{N}]++|\s++$|\s+(?!\S)|\s/u',
      'special' => ['<|endoftext|>' => 50256],
    ],
    'cl100k_base' => [
      'file'    => 'cl100k_base',
      'pattern' => '/\'(?i:[sdmt]|ll|ve|re)|[^\r\n\p{L}\p{N}]?+\p{L}++|\p{N}{1,3}+| ?[^\s\p{L}\p{N}]++[\r\n]*+|\s++$|\s*[\r\n]|\s+(?!\S)|\s/u',
      'special' => [
        '<|endoftext|>'   => 100257,
        '<|fim_prefix|>'  => 100258,
        '<|fim_middle|>'  => 100259,
        '<|fim_middle|>'  => 100260,
        '<|endofprompt|>' => 100276,
      ],
    ],
    'o200k_base'  => [
      'file'    => 'o200k_base',
      'pattern' => '/[^\r\n\p{L}\p{N}]?[\p{Lu}\p{Lt}\p{Lm}\p{Lo}\p{M}]*[\p{Ll}\p{Lm}\p{Lo}\p{M}]+(?i:\'s|\'t|\'re|\'ve|\'m|\'ll|\'d)?|[^\r\n\p{L}\p{N}]?[\p{Lu}\p{Lt}\p{Lm}\p{Lo}\p{M}]+[\p{Ll}\p{Lm}\p{Lo}\p{M}]*(?i:\'s|\'t|\'re|\'ve|\'m|\'ll|\'d)?|\p{N}{1,3}| ?[^\s\p{L}\p{N}]+[\r\n\/]*|\s*[\r\n]+|\s+(?!\S)|\s+/u',
      'special' => [
        '<|endoftext|>'   => 199999,
        '<|endofprompt|>' => 200018,
      ],
    ],
    'o200k_harmony' => [
      'file'    => 'o200k_base',
      'pattern' => '/[^\r\n\p{L}\p{N}]?[\p{Lu}\p{Lt}\p{Lm}\p{Lo}\p{M}]*[\p{Ll}\p{Lm}\p{Lo}\p{M}]+(?i:\'s|\'t|\'re|\'ve|\'m|\'ll|\'d)?|[^\r\n\p{L}\p{N}]?[\p{Lu}\p{Lt}\p{Lm}\p{Lo}\p{M}]+[\p{Ll}\p{Lm}\p{Lo}\p{M}]*(?i:\'s|\'t|\'re|\'ve|\'m|\'ll|\'d)?|\p{N}{1,3}| ?[^\s\p{L}\p{N}]+[\r\n\/]*|\s*[\r\n]+|\s+(?!\S)|\s+/u',
      'special' => [
        '<|endoftext|>'       => 199999,
        '<|endofprompt|>'     => 200018,
        '<|startoftext|>'     => 199998,
        '<|endoftext|>'       => 199999,
        '<|reserved_200000|>' => 200000,
        '<|reserved_200001|>' => 200001,
        '<|return|>'          => 200002,
        '<|constrain|>'       => 200003,
        '<|reserved_200004|>' => 200004,
        '<|channel|>'         => 200005,
        '<|start|>'           => 200006,
        '<|end|>'             => 200007,
        '<|message|>'         => 200008,
        '<|reserved_200009|>' => 200009,
        '<|reserved_200010|>' => 200010,
        '<|reserved_200011|>' => 200011,
        '<|call|>'            => 200012,
      ],
    ],
  ];

  /** Returns the tiktoken file */
  public function file(): string { return self::$definitions[$this->name]['file']; }

  /** Returns the pattern associated with this encoding */
  public function pattern(): string { return self::$definitions[$this->name]['pattern']; }

  /** Returns special tokens encoding */
  public function special(): string { return self::$definitions[$this->name]['special']; }

  /** Loads encoder with vocabulary from a given source */
  public function load(Source $source): Encoder {
    return new Encoder($source->tokens(self::$definitions[$this->name]['file']), $this);
  }

  /**
   * Returns an encoding for a given name
   *
   * @throws  lang.IllegalArgumentException
   */
  public static function named(string $name): self {
    return parent::valueOf(self::class, $name);
  }
}