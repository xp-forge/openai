<?php namespace com\openai\unittest;

use com\openai\realtime\RealtimeApi;
use lang\IllegalStateException;
use peer\Socket;
use test\{Assert, Expect, Test, Values};

class RealtimeApiTest {
  const URI= 'wss://api.openai.com/v1/realtime?model=gpt-4o-realtime-preview-2024-10-01';
  const SESSION_CREATED= '{"type": "session.created"}';

  /** Returns authentications */
  private function authentications(): iterable {
    yield ['azure', ['api-key' => 'test']];
    yield ['openai', ['Authorization' => 'Bearer test', 'OpenAI-Beta' => 'realtime=v1']];
  }

  #[Test]
  public function can_create() {
    new RealtimeApi(self::URI);
  }

  #[Test]
  public function initially_not_connected() {
    $c= new RealtimeApi(new TestingSocket());

    Assert::false($c->connected());
  }

  #[Test]
  public function socket_accessor() {
    Assert::instance(Socket::class, (new RealtimeApi(self::URI))->socket());
  }

  #[Test]
  public function connect() {
    $c= new RealtimeApi(new TestingSocket([self::SESSION_CREATED]));
    $c->connect();

    Assert::true($c->connected());
  }

  #[Test, Values(from: 'authentications')]
  public function passing_headers($kind, $headers) {
    $s= new TestingSocket([self::SESSION_CREATED]);

    $c= new RealtimeApi($s);
    $c->connect($headers);

    Assert::equals($headers, $s->connected);
  }

  #[Test]
  public function close() {
    $c= new RealtimeApi(new TestingSocket([self::SESSION_CREATED]));
    $c->connect();
    $c->close();

    Assert::false($c->connected());
  }

  #[Test]
  public function initial_handshake() {
    $c= new RealtimeApi(new TestingSocket([self::SESSION_CREATED]));
    $session= $c->connect();

    Assert::equals(['type' => 'session.created'], $session);
  }

  #[Test, Expect(class: IllegalStateException::class, message: 'Unexpected handshake event "error"')]
  public function unexpected_handshake() {
    $c= new RealtimeApi(new TestingSocket(['{"type":"error"}']));
    $c->connect();
  }

  #[Test]
  public function update_session() {
    $c= new RealtimeApi(new TestingSocket([
      self::SESSION_CREATED,
      '{"type": "session.update", "session": {"instructions": "You are TestGPT"}}',
      '{"type": "session.updated"}',
    ]));
    $c->connect();
    $c->send(['type' => 'session.update', 'session' => ['instructions' => 'You are TestGPT']]);

    Assert::equals(['type' => 'session.updated'], $c->receive());
  }

  #[Test]
  public function transmit() {
    $c= new RealtimeApi(new TestingSocket([
      self::SESSION_CREATED,
      '{"type": "conversation.item.create", "item": {"type": "message"}}',
      '{"type": "conversation.item.created"}',
    ]));
    $c->connect();
    $response= $c->transmit(['type' => 'conversation.item.create', 'item' => ['type' => 'message']]);

    Assert::equals(['type' => 'conversation.item.created'], $response);
  }

  #[Test]
  public function string_representation() {
    Assert::equals(
      'com.openai.realtime.RealtimeApi(->wss://api.openai.com:443/v1/realtime?model=gpt-4o-realtime-preview-2024-10-01)',
      (new RealtimeApi(self::URI))->toString()
    );
  }
}