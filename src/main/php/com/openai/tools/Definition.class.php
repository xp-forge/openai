<?php namespace com\openai\tools;

/** A tool definition */
class Definition {
  public $name, $description, $input;

  public function __construct($name, $description, $input) {
    $this->name= $name;
    $this->description= $description;
    $this->input= $input;
  }
}