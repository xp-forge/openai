<?php namespace com\openai\tools;

/** Base class for Grouped and Namespaced */
abstract class Scoped extends Definitions {
  protected $tools= [];

  /** @param [:(com.openai.tools.Named|[:callable]|object)]|object $arg */
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

  /** @param com.openai.tools.Named|[:callable]|object $impl */
  public function add(string $scope, $impl): self {
    $this->tools[$scope]= $impl instanceof Named ? $impl : new Named($impl);
    return $this;
  }

  /** @return string */
  public function toString() {
    $s= nameof($this)."('{$this->description}')";
    if (empty($this->tools)) return $s.' {}';

    $s.= " {\n";
    foreach ($this->tools as $scope => $definitions) {
      $s.= "  - {$scope}\n";
      foreach ($definition->schema() as $definition) {
        $s.= "    {$definition->name}(): {$description->description}\n";
      }
    }
    return $s."\n}";
  }
}