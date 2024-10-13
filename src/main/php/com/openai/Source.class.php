<?php namespace com\openai;

/** Base class for all source implementations */
abstract class Source {

  /** Yields the tokens and their associated ranks from this source */
  public abstract function tokens(string $source): iterable;
}
