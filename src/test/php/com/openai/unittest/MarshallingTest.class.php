<?php namespace com\openai\unittest;

use com\openai\tools\{Named, Calls, Context};
use test\{Assert, Test};
use util\{Date, TimeZone};

class MarshallingTest {

  #[Test]
  public function unmarshal_date() {
    $calls= new Calls(new Named([
      'fixture' => function(Date $in) {
        return $in->toString('d.m.Y');
      }
    ]));

    Assert::equals('"26.10.2024"', $calls->call('fixture', '{"in": "2024-10-26"}'));
  }

  #[Test]
  public function marshal_date() {
    $calls= new Calls(new Named([
      'fixture' => function($in) {
        return new Date($in, TimeZone::getByName('UTC'));
      }
    ]));

    Assert::equals('"2024-10-26T00:00:00+0000"', $calls->call('fixture', '{"in": "2024-10-26"}'));
  }
}