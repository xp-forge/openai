<?php namespace com\openai\rest;

use lang\Value;

/** @see https://platform.openai.com/docs/guides/rate-limits/rate-limits-in-headers */
class RateLimit implements Value {
  public $remaining= null;

  /** Update rate limit if header is present */
  public function update(?int $remaining): void {
    null === $remaining || $this->remaining= $remaining;
  }

  /** @return string */
  public function toString() {
    return nameof($this).'(remaining: '.(null === $this->remaining ? '(n/a)' : $this->remaining).')';
  }

  /** @return string */
  public function hashCode() {
    return 'R'.$this->remaining;
  }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? $this->remaining <=> $value->remaining : 1;
  }
}