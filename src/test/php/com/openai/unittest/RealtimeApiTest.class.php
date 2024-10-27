<?php namespace com\openai\unittest;

use com\openai\realtime\RealtimeApi;
use test\{Assert, Test, Values};

class RealtimeApiTest {

  /** Returns authentications */
  private function authentications(): iterable {
    yield ['azure', ['api-key' => 'test']];
    yield ['openai', ['Authorization' => 'Bearer test', 'OpenAI-Beta' => 'realtime=v1']];
  }

  #[Test]
  public function can_create() {
    new RealtimeApi('wss://api.openai.com/v1/realtime?model=gpt-4o-realtime-preview-2024-10-01');
  }

  #[Test]
  public function initially_not_connected() {
    $c= new RealtimeApi(new TestingSocket());

    Assert::false($c->connected());
  }

  #[Test]
  public function connect() {
    $c= new RealtimeApi(new TestingSocket());
    $c->connect();

    Assert::true($c->connected());
  }

  #[Test, Values(from: 'authentications')]
  public function passing_headers($kind, $headers) {
    $s= new TestingSocket();

    $c= new RealtimeApi($s);
    $c->connect($headers);

    Assert::equals($headers, $s->connected);
  }

  #[Test]
  public function close() {
    $c= new RealtimeApi(new TestingSocket());
    $c->connect();
    $c->close();

    Assert::false($c->connected());
  }

  #[Test]
  public function initial_handshake() {
    $c= new RealtimeApi(new TestingSocket([
      '{"type": "session.created"}',
    ]));
    $c->connect();

    Assert::equals(['type' => 'session.created'], $c->receive());
  }

  #[Test]
  public function update_session() {
    $c= new RealtimeApi(new TestingSocket([
      '{"type": "session.created"}',
      '{"type": "session.update", "session": {"instructions": "You are TestGPT"}}',
      '{"type": "session.updated"}',
    ]));
    $c->connect();
    $c->receive();
    $c->send(['type' => 'session.update', 'session' => ['instructions' => 'You are TestGPT']]);

    Assert::equals(['type' => 'session.updated'], $c->receive());
  }

  #[Test]
  public function transmit() {
    $c= new RealtimeApi(new TestingSocket([
      '{"type": "session.created"}',
      '{"type": "conversation.item.create", "item": {"type": "message"}}',
      '{"type": "conversation.item.created"}',
    ]));
    $c->connect();
    $c->receive();
    $response= $c->transmit(['type' => 'conversation.item.create', 'item' => ['type' => 'message']]);

    Assert::equals(['type' => 'conversation.item.created'], $response);
  }
}