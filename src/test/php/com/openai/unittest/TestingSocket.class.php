<?php namespace com\openai\unittest;

use lang\IllegalStateException;
use websocket\WebSocket;

class TestingSocket extends WebSocket {
  private $messages;
  private $connected= null;

  public function __construct($messages= []) {
    $this->messages= $messages;
  }

  public function connected() {
    return isset($this->connected);
  }

  public function connect($headers= []) {
    $this->connected= $headers;
  }

  public function send($payload) {
    $message= array_shift($this->messages);
    if (json_decode($message, true) !== json_decode($payload, true)) {
      throw new IllegalStateException('Unexpected '.$payload.', expecting '.$message);
    }
  }

  public function receive($timeout= null) {
    return array_shift($this->messages);
  }

  public function close($code= 1000, $reason = '') {
    $this->connected= null;
  }
}