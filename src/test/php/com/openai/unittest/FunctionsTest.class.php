<?php namespace com\openai\unittest;

use com\openai\tools\{Functions, Param, Context};
use lang\{XPClass, IllegalArgumentException};
use test\{Assert, Expect, Test, Values};

class FunctionsTest {
  const HELLO_WORLD= [
    'testing_hello' => [
      'description' => 'Hello World',
      'input'       => [
        'type'       => 'object',
        'properties' => ['name' => ['type' => 'string', 'description' => 'Name']],
        'required'   => [],
      ],
    ]
  ];

  /** For select() test */
  private function selections(): iterable {
    yield [[], []];
    yield [['testing_*'], ['testing_code', 'testing_execute']];
    yield [['testing_execute'], ['testing_execute']];
    yield [['testing_execute', 'production_*'], ['testing_execute', 'production_execute']];
    yield [['testing_nonexistant'], []];
    yield [['nonexistant_*'], []];
  }

  #[Test]
  public function can_create() {
    new Functions();
  }

  #[Test]
  public function register() {
    Assert::equals(
      self::HELLO_WORLD,
      iterator_to_array((new Functions())->register('testing', new HelloWorld())->schema()),
    );
  }

  #[Test]
  public function with_classname() {
    Assert::equals(
      self::HELLO_WORLD,
      iterator_to_array((new Functions())->with('testing', HelloWorld::class)->schema()),
    );
  }

  #[Test]
  public function with_xpclass() {
    Assert::equals(
      self::HELLO_WORLD,
      iterator_to_array((new Functions())->with('testing', XPClass::forName('com.openai.unittest.HelloWorld'))->schema()),
    );
  }

  #[Test]
  public function required_parameter() {
    $fixture= (new Functions())->register('testing', new class() {
      private $hello= 'Hello';

      /** Greets the user */
      public function greet($name) {
        return "{$this->hello} {$name}";
      }
    });

    Assert::equals(
      [
        'type'       => 'object',
        'properties' => [
          'name' => ['type' => 'string', 'description' => 'Name']
        ],
        'required'   => ['name'],
      ],
      $fixture->schema()->current()['input'],
    );
  }

  #[Test]
  public function optional_parameter() {
    $fixture= (new Functions())->register('testing', new class() {

      /** Greets the user */
      public function greet($name= 'World') {
        return "Hello {$name}";
      }
    });

    Assert::equals(
      [
        'type'       => 'object',
        'properties' => [
          'name' => ['type' => 'string', 'description' => 'Name']
        ],
        'required'   => [],
      ],
      $fixture->schema()->current()['input'],
    );
  }

  #[Test]
  public function context_parameter() {
    $fixture= (new Functions())->register('testing', new class() {

      /** Greets the user */
      public function greet(
        #[Context]
        $name
      ) {
        return "Hello {$name}";
      }
    });

    Assert::equals(
      [
        'type'       => 'object',
        'properties' => (object)[],
        'required'   => [],
      ],
      $fixture->schema()->current()['input'],
    );
  }

  #[Test]
  public function annotated_parameter() {
    $fixture= (new Functions())->register('testing', new class() {

      /** Greets the user */
      public function greet(
        #[Param("The user's name")]
        $name
      ) {
        return "Hello {$name}";
      }
    });

    Assert::equals(
      [
        'type'       => 'object',
        'properties' => [
          'name' => ['type' => 'string', 'description' => "The user's name"]
        ],
        'required'   => ['name'],
      ],
      $fixture->schema()->current()['input'],
    );
  }

  #[Test]
  public function annotated_parameter_with_enum() {
    $fixture= (new Functions())->register('testing', new class() {

      /** Returns 100 degrees in the given unit */
      public function temperature(
        #[Param(type: ['type' => 'string', 'enum' => ['celsius', 'fahrenheit']])]
        $unit
      ) {
        return "It's 100° ".ucfirst($unit);
      }
    });

    Assert::equals(
      [
        'type'       => 'object',
        'properties' => [
          'unit' => ['type' => 'string', 'enum' => ['celsius', 'fahrenheit']]
        ],
        'required'   => ['unit'],
      ],
      $fixture->schema()->current()['input'],
    );
  }

  #[Test]
  public function annotated_parameter_with_type() {
    $fixture= (new Functions())->register('testing', new class() {

      /** Returns top X */
      public function top(
        #[Param(type: 'number')]
        $count= 10
      ) {
        return "Top {$count}";
      }
    });

    Assert::equals(
      [
        'type'       => 'object',
        'properties' => [
          'count' => ['type' => 'number']
        ],
        'required'   => [],
      ],
      $fixture->schema()->current()['input'],
    );
  }

  #[Test, Values(from: 'selections')]
  public function select($namespaces, $expected) {
    $fixture= (new Functions())
      ->register('testing', new class() {
        public function code() { return 'coded'; }
        public function execute() { return 'simulated'; }
      })
      ->register('production', new class() {
        public function execute() { return 'executed'; }
      })
    ;

    $result= [];
    foreach ($fixture->select($namespaces)->schema() as $name => $description) {
      $result[]= $name;
    }
    Assert::equals($expected, $result);
  }

  #[Test, Values([[[], 'Hello World'], [['name' => 'Test'], 'Hello Test']])]
  public function invoke($arguments, $expected) {
    $fixture= (new Functions())->with('testing', HelloWorld::class);
    $result= $fixture->invoke('testing_hello', $arguments);

    Assert::equals($expected, $result);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function unknown_namespace() {
    $fixture= (new Functions())->with('testing', HelloWorld::class);
    $fixture->invoke('unknown_hello', []);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function unknown_method() {
    $fixture= (new Functions())->with('testing', HelloWorld::class);
    $fixture->invoke('testing_unknown', []);
  }
}