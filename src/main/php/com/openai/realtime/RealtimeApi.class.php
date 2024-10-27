<?php namespace com\openai\realtime;

use text\json\Json;
use util\URI;
use util\data\Marshalling;
use util\log\Traceable;
use websocket\WebSocket;

/** @see https://github.com/azure-samples/aoai-realtime-audio-sdk */
class RealtimeApi implements Traceable {
  private $ws, $headers;
  private $cat= null;

  /** @param string|util.URI $endpoint */
  public function __construct($endpoint) {
    $uri= $endpoint instanceof URI ? $endpoint : new URI($endpoint);
    $this->ws= new WebSocket($uri);
    $this->headers= ['api-key' => $uri->user()];
    $this->marshalling= new Marshalling();
  }

  /** @param ?util.log.LogCategory $cat */
  public function setTrace($cat) {
    $this->cat= $cat;
  }

  /** Opens the underlying websocket, optionally passing headers */
  public function connect(array $headers= []): self {
    $headers+= $this->headers;
    $this->cat && $this->cat->info($this->ws->socket(), $this->ws->path(), $headers);
    $this->ws->connect($headers);
    return $this;
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