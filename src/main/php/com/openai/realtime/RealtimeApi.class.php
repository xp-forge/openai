<?php namespace com\openai\realtime;

use lang\IllegalStateException;
use text\json\Json;
use util\data\Marshalling;
use util\log\Traceable;
use util\URI;
use websocket\WebSocket;

/**
 * OpenAI Realtime API enables you to build low-latency, multi-modal conversational
 * experiences. It currently supports text and audio as both input and output, as
 * well as function calling.
 *
 * @test  com.openai.unittest.RealtimeApiTest
 * @see   https://platform.openai.com/docs/guides/realtime
 */
class RealtimeApi implements Traceable {
  private $ws, $marshalling;
  private $cat= null;

  /** @param string|util.URI|websocket.WebSocket $endpoint */
  public function __construct($endpoint) {
    $this->ws= $endpoint instanceof WebSocket ? $endpoint : new WebSocket((string)$endpoint);
    $this->marshalling= new Marshalling();
  }

  /** @param ?util.log.LogCategory $cat */
  public function setTrace($cat) {
    $this->cat= $cat;
  }

  /**
   * Opens the underlying websocket, optionally passing headers
   *
   * Verifies a `session.created` event is received. This is sent by the server
   * as soon as the connection is successfully established. Provides a connection-
   * specific ID that may be useful for debugging or logging.
   *
   * @return var
   * @throws lang.IllegalStateException
   */
  public function connect(array $headers= []) {
    $this->cat && $this->cat->info($this->ws->socket(), $this->ws->path(), $headers);
    $this->ws->connect($headers);

    $event= $this->receive();
    if ('session.created' === ($event['type'] ?? null)) return $event;

    $error= 'Unexpected handshake event "'.($event['type'] ?? '(null)').'"';
    $this->ws->close(4007, $error);
    throw new IllegalStateException($error);
  }

  /** Returns whether the underlying websocket is connected */
  public function connected(): bool {
    return $this->ws->connected();
  }

  /** Closes the underlying websocket */
  public function close(): void {
    $this->ws->close();
  }

  /**
   * Sends a given payload. Doesn't wait for a response
   *
   * @param  var $payload
   * @return void
   */
  public function send($payload): void {
    $json= Json::of($this->marshalling->marshal($payload));
    $this->cat && $this->cat->debug('>>>', $json);
    $this->ws->send($json);
  }

  /**
   * Receives an answer. Returns NULL if EOF is reached.
   *
   * @return var
   */
  public function receive() {
    $json= $this->ws->receive();
    $this->cat && $this->cat->debug('<<<', $json);
    return null === $json ? null : $this->marshalling->unmarshal(Json::read($json));
  }

  /**
   * Sends a given payload and returns the response to it.
   *
   * @param  var $payload
   * @return var
   */
  public function transmit($payload) {
    $this->send($payload);
    return $this->receive();
  }

  /** Ensures socket is closed */
  public function __destruct() {
    $this->ws && $this->ws->close();
  }
}