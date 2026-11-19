<?php namespace com\openai\v4a;

class Operation {
  public $type, $path, $hunks;
  public $target= null;

  public function __construct(string $type, $path, array $hunks= []) {
    $this->type= $type;
    if (is_array($path)) {
      $this->path= key($path);
      $this->target= current($path);
    } else {
      $this->path= $path;
    }
    $this->hunks= $hunks;
  }
}