<?php namespace com\openai\unittest;

use com\openai\Tools;
use com\openai\realtime\RealtimeApi;
use com\openai\rest\OpenAIEndpoint;
use com\openai\tools\Functions;
use test\{Assert, Test, Values};
use webservices\rest\TestEndpoint;

class ToolsTest {

  /** Returns a testing API endpoint */
  private function testingEndpoint(): TestEndpoint {
    return new TestEndpoint([
      'POST /echo' => function($call) {
        return $call->respond(200, 'OK', ['Content-Type' => 'application/json'], $call->content());
      }
    ]);
  }

  /** Returns functions with a "Hello World!" registration */
  private function functions(): Functions {
    return (new Functions())->register('greet', new class() {
      public function world($name= 'World') { return "Hello {$name}!"; }
    });
  }

  #[Test]
  public function can_create() {
    new Tools();
  }

  #[Test, Values([['code_interpreter'], [['type' => 'code_interpreter']]])]
  public function code_interpreter($tool) {
    Assert::equals([['type' => 'code_interpreter']], (new Tools($tool))->selection);
  }

  #[Test]
  public function with_custom_functions() {
    $functions= $this->functions();
    Assert::equals([$functions], (new Tools($functions))->selection);
  }

  #[Test]
  public function serialized_for_rest_api() {
    $functions= $this->functions();
    $endpoint= new OpenAIEndpoint($this->testingEndpoint());
    $result= $endpoint->api('/echo')->invoke(['tools' => new Tools($functions)]);

    Assert::equals(
      ['tools' => [[
        'type' => 'function',
        'function' => [
          'name'        => 'greet_world',
          'description' => 'World',
          'parameters'  => $functions->schema()->current()['input'],
        ],
      ]]],
      $result
    );
  }

  #[Test]
  public function serialized_for_realtime_api() {
    $functions= $this->functions();
    $api= new RealtimeApi(new TestingSocket([
      '{"type": "session.created"}',
      '{"type": "session.update", "session": {
        "tools": [{
          "type": "function",
          "name": "greet_world",
          "description": "World",
          "parameters": {
            "type": "object",
            "properties": {
              "name": {"type": "string", "description": "Name"}
            },
            "required": []
          }
        }]
      }}',
    ]));
    $api->connect();
    $api->send(['type' => 'session.update', 'session' => ['tools' => new Tools($functions)]]);
  }
}