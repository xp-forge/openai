<?php namespace com\openai\tools;

/** @test com.openai.unittest.GroupedTest */
class Grouped extends Scoped {

  /** Returns schema for these functions */
  public function schema(): iterable {
    foreach ($this->tools as $scope => $tools) {
      yield from $tools->schema($scope.'_');
    }
  }

  /** @return ?ReflectionFunction */
  public function invocable(string $name, ?string $namespace= null) {
    if (null !== $namespace) return null;

    // Name gets passed in as `[scope]_[function]`
    sscanf($name, "%[^_]_%[^\r]", $scope, $function);
    return ($tools= $this->tools[$scope] ?? null) ? $tools->invocable($function, null) : null;
  }
}