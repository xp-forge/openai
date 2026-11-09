<?php namespace com\openai\tools;

/** @test com.openai.unittest.NamespacedTest */
class Namespaced extends Scoped {

  /** Returns schema for these functions */
  public function schema(): iterable {
    foreach ($this->tools as $namespace => $tools) {
      yield [
        'type'        => 'namespace',
        'name'        => $namespace,
        'description' => $tools->description ?? ucfirst($namespace),
        'tools'       => [...$tools->schema()],
      ];
    }
  }

  /** @return ?ReflectionFunction */
  public function invocable(string $name, ?string $namespace= null) {
    return ($tools= $this->tools[$namespace] ?? null) ? $tools->invocable($name, null) : null;
  }
}