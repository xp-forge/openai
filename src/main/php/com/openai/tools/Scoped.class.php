<?php namespace com\openai\tools;

/** Base class for Grouped and Namespaced */
abstract class Scoped extends Definitions {
  protected $tools= [];

  /** @param [:(parent|[:callable]|object)]|object $arg */
  public function __construct($arg= []) {
    if (is_array($arg)) {
      foreach ($arg as $scope => $impl) {
        $this->add($scope, $impl);  
      }
    } else {

      // Use lowercase version of class' declared name for scope
      $decl= false === ($p= strrpos($arg::class, '\\')) ? $arg::class : substr($arg::class, $p + 1);
      $this->add(strtolower($decl), $arg);
    }
  }

  /** @param parent|[:callable]|object $impl */
  public function add(string $scope, $impl, bool $defer= false): self {
    $this->tools[$scope]= [$impl instanceof parent ? $impl : new Named($impl), $defer];
    return $this;
  }

  /** @return string */
  public function toString() {
    $s= nameof($this)."('{$this->description}')";
    if (empty($this->tools)) return $s.' {}';

    $s.= " {\n";
    foreach ($this->tools as $scope => [$definitions, $defer]) {
      $s.= "  - {$scope}\n";
      foreach ($definition->schema() as $definition) {
        $s.= "    {$definition->name}(): {$description->description}\n";
      }
    }
    return $s."\n}";
  }
}