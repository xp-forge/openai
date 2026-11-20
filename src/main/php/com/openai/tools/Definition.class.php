<?php namespace com\openai\tools;

/** A tool definition */
class Definition {
  public $name, $description, $input, $defer;

  public function __construct($name, $description, $input, $defer= false) {
    $this->name= $name;
    $this->description= $description;
    $this->input= $input;
    $this->defer= $defer;
  }
}