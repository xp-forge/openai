<?php namespace com\openai\rest;

use IteratorAggregate, Traversable;
use io\streams\{InputStream, StringReader};
use lang\IllegalStateException;
use util\Objects;

/**
 * Streams events using SSE with JSON data
 *
 * @see   https://platform.openai.com/docs/guides/structured-outputs?api-mode=responses
 * @see   https://html.spec.whatwg.org/multipage/server-sent-events.html#server-sent-events
 * @test  com.openai.unittest.EventsTest
 */
class Events implements IteratorAggregate {
  const DONE= '[DONE]';
  private $stream;

  /** Creates a new event stream */
  public function __construct(InputStream $stream) {
    $this->stream= $stream;
  }

  /** Returns events while reading */
  public function getIterator(): Traversable {
    $r= new StringReader($this->stream);
    $event= null;

    // Read all lines starting with `event` or `data`, ignore others
    while (null !== ($line= $r->readLine())) {
      if (0 === strncmp($line, 'event: ', 6)) {
        $event= substr($line, 7);
      } else if (0 === strncmp($line, 'data: ', 5)) {
        $data= substr($line, 6);
        if (self::DONE !== $data) yield $event => json_decode($data, true);
        $event= null;
      }
    }
  }
}