<?php namespace com\openai\unittest;

use com\openai\tools\{Namespaced, Named, Instances};
use test\{Assert, Test, Values};

class InstancesTest {

  /** Invokes given fixtures and return how many instances were created */
  private function invoke(Instances $fixture, int $times): int {
    Instantiation::$counter= 0;

    $calls= (new Namespaced(['test' => $fixture]))->calls();
    for ($i= 0; $i < $times; $i++) {
      $calls->invoke('hello', ['name' => 'Test'], [], namespace: 'test');
    }

    return Instantiation::$counter;
  }

  #[Test, Values([[0, 0], [1, 1], [3, 3]])]
  public function new($times, $instances) {
    Assert::equals($instances, $this->invoke(Instances::new(Instantiation::class), $times));
  }

  #[Test, Values([[0, 0], [1, 1], [3, 1]])]
  public function single($times, $instances) {
    Assert::equals($instances, $this->invoke(Instances::single(Instantiation::class), $times));
  }

  #[Test, Values(['new', 'single'])]
  public function control_instantiation($kind) {
    $instances= Instances::$kind(Instantiation::class, fn() => new Instantiation('Hallo'));
    Assert::equals(
      'Hallo Test',
      (new Named($instances))->calls()->invoke('hello', ['name' => 'Test'])
    );
  }
}