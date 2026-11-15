<?php namespace com\openai\tools;

/** @test com.openai.unittest.NamespacedTest */
class Namespaced extends Scoped {

  /** Returns schema for these functions, inheriting the `defer` switch */
  public function schema(): iterable {
    foreach ($this->tools as $namespace => [$definitions, $defer]) {
      $tools= [];
      foreach ($definitions->schema() as $definition) {
        $definition->defer= $defer;
        $tools[]= $definition;
      }

      yield [
        'type'        => 'namespace',
        'name'        => $namespace,
        'description' => $definitions->description ?? ucfirst($namespace),
        'tools'       => $tools,
      ];
    }
  }

  /** @return ?lang.reflection.Invocable */
  public function invocable(string $name, ?string $namespace= null) {
    return ($tools= $this->tools[$namespace] ?? null) ? $tools[0]->invocable($name, null) : null;
  }
}