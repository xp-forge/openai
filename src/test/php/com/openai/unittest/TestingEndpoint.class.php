<?php namespace com\openai\unittest;

use com\openai\rest\OpenAIEndpoint;
use webservices\rest\TestEndpoint;

trait TestingEndpoint {

  /** Returns a testing API endpoint */
  private function testingEndpoint($remaining): OpenAIEndpoint {
    $endpoint= new OpenAIEndpoint(new TestEndpoint([
      'POST /chat/completions' => function($call) use(&$remaining) {
        $remaining--;
        return $call->respond(
          200, 'OK',
          ['x-ratelimit-remaining-requests' => max(0, $remaining), 'Content-Type' => 'application/json'],
          '{"choices":[{"message":{"role":"assistant","content":"Test"}}]}'
        );
      }
    ]));

    // Normally this is not done until after the API has been invoked, for
    // ease of testing purposes we'll set it here.
    $endpoint->rateLimit()->remaining= $remaining;
    return $endpoint;
  }
}