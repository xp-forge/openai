<?php namespace com\openai\rest;

use io\streams\{InputStream, StringReader};
use lang\IllegalStateException;
use util\Objects;

/**
 * OpenAI API event stream
 *
 * @see   https://platform.openai.com/docs/guides/production-best-practices/streaming
 * @test  com.openai.unittest.EventStreamTest
 */
class EventStream {
  private $stream;
  private $result= null;

  /** Creates a new event stream */
  public function __construct(InputStream $stream) {
    $this->stream= $stream;
  }

  /**
   * Apply a given value with a delta
   *
   * @param  var $result
   * @param  string|int $field
   * @param  var $delta
   * @return void
   * @throws lang.IllegalStateException
   */
  private function apply(&$result, $field, $delta) {
    if (null === $delta) {
      // NOOP
    } else if (is_string($delta)) {
      $result[$field]??= '';
      $result[$field].= $delta;
    } else if (is_int($delta) || is_float($delta)) {
      $result[$field]??= 0;
      $result[$field]+= $delta;
    } else if (is_array($delta)) {
      if (isset($delta['index'])) {
        $ptr= &$result[$delta['index']];
        unset($delta['index']);
      } else {
        $ptr= &$result[$field];
      }
      $ptr??= [];
      foreach ($delta as $key => $val) {
        $this->apply($ptr, $key, $val);
      }
    } else {
      throw new IllegalStateException('Cannot apply delta '.Objects::stringOf($delta));
    }
  }

  /**
   * Merge a given value with the result, yielding any deltas
   *
   * @param  var $result
   * @param  var $value
   * @return iterable
   * @throws lang.IllegalStateException
   */
  private function merge(&$result, $value) {
    if (is_array($value)) {
      $result??= [];
      foreach ($value as $key => $val) {
        if ('delta' === $key) {
          foreach ($val as $field => $delta) {
            yield $field => $delta;
            $this->apply($result['message'], $field, $delta);
          }
        } else {
          yield from $this->merge($result[$key], $val);
        }
      }
    } else {
      $result= $value;
    }
  }

  /**
   * Returns delta pairs while reading
   *
   * @throws lang.IllegalStateException
   */
  public function deltas(?string $filter= null): iterable {
    if (null !== $this->result) {
      throw new IllegalStateException('Event stream already consumed');
    }

    $r= new StringReader($this->stream);
    while (null !== ($line= $r->readLine())) {
      if (0 !== strncmp($line, 'data: ', 5)) continue;
      // echo "\n<<< $line\n";

      // Last chunk is "data: [DONE]"
      $data= substr($line, 6);
      if ('[DONE]' === $data) break;

      // Process deltas, applying them to our result while simultaneously
      // yielding them back to our caller.
      foreach ($this->merge($this->result, json_decode($data, true)) as $field => $delta) {
        if (null === $filter || $filter === $field) yield $field => $delta;
      }
    }
    $this->stream->close();
  }

  /** Returns the result, fetching deltas if necessary */
  public function result(): array {
    if (null === $this->result) iterator_count($this->deltas());
    return $this->result;
  }
}