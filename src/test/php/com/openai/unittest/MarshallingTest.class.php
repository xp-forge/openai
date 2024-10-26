<?php namespace com\openai\unittest;

use com\openai\tools\{Functions, Calls, Context};
use test\{Assert, Test};
use util\{Date, TimeZone};

class MarshallingTest {

  #[Test]
  public function unmarshal_date() {
    $calls= new Calls((new Functions())->register('testing', new class() {
      public function date(Date $in) {
        return $in->toString('d.m.Y');
      }
    }));

    Assert::equals('26.10.2024', $calls->invoke('testing_date', ['in' => '2024-10-26']));
  }

  #[Test]
  public function marshal_date() {
    $calls= new Calls((new Functions())->register('testing', new class() {
      public function date($in) {
        return new Date($in, TimeZone::getByName('UTC'));
      }
    }));

    Assert::equals('2024-10-26T00:00:00+0000', $calls->invoke('testing_date', ['in' => '2024-10-26']));
  }
}