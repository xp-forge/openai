<?php namespace com\openai;

use lang\Enum;

/** @see https://github.com/openai/tiktoken/blob/main/tiktoken_ext/openai_public.py */
class Encoding extends Enum {
  const ENDOFTEXT  = '<|endoftext|>';
  const FIM_PREFIX = '<|fim_prefix|>';
  const FIM_MIDDLE = '<|fim_middle|>';
  const FIM_SUFFIX = '<|fim_suffix|>';
  const ENDOFPROMPT= '<|endofprompt|>';

  public static $r50k_base, $p50k_base, $cl100k_base, $o200k_base;
  private static $definitions= [
    'r50k_base'   => [
      'pattern' => '/\'(?:[sdmt]|ll|ve|re)| ?\p{L}++| ?\p{N}++| ?[^\s\p{L}\p{N}]++|\s++$|\s+(?!\S)|\s/u',
      'special' => [self::ENDOFTEXT => 50256],
    ],
    'p50k_base'   => [
      'pattern' => '/\'(?:[sdmt]|ll|ve|re)| ?\p{L}++| ?\p{N}++| ?[^\s\p{L}\p{N}]++|\s++$|\s+(?!\S)|\s/u',
      'special' => [self::ENDOFTEXT => 50256],
    ],
    'cl100k_base' => [
      'pattern' => '/\'(?i:[sdmt]|ll|ve|re)|[^\r\n\p{L}\p{N}]?+\p{L}++|\p{N}{1,3}+| ?[^\s\p{L}\p{N}]++[\r\n]*+|\s++$|\s*[\r\n]|\s+(?!\S)|\s/u',
      'special' => [
        self::ENDOFTEXT   => 100257,
        self::FIM_PREFIX  => 100258,
        self::FIM_MIDDLE  => 100259,
        self::FIM_SUFFIX  => 100260,
        self::ENDOFPROMPT => 100276,
      ],
    ],
    'o200k_base'  => [
      'pattern' => '/[^\r\n\p{L}\p{N}]?[\p{Lu}\p{Lt}\p{Lm}\p{Lo}\p{M}]*[\p{Ll}\p{Lm}\p{Lo}\p{M}]+(?i:\'s|\'t|\'re|\'ve|\'m|\'ll|\'d)?|[^\r\n\p{L}\p{N}]?[\p{Lu}\p{Lt}\p{Lm}\p{Lo}\p{M}]+[\p{Ll}\p{Lm}\p{Lo}\p{M}]*(?i:\'s|\'t|\'re|\'ve|\'m|\'ll|\'d)?|\p{N}{1,3}| ?[^\s\p{L}\p{N}]+[\r\n\/]*|\s*[\r\n]+|\s+(?!\S)|\s+/u',
      'special' => [self::ENDOFTEXT => 199999, self::ENDOFPROMPT => 200018],
    ],
  ];

  /** Returns the pattern associated with this encoding */
  public function pattern(): string { return self::$definitions[$this->name]['pattern']; }

  /** Returns special tokens encoding */
  public function special(): string { return self::$definitions[$this->name]['special']; }

  /** Loads encoder with vocabulary from a given source */
  public function load(Source $source): Encoder {
    return new Encoder($source->tokens($this->name), $this);
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