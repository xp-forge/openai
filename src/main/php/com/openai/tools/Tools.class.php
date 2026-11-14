<?php namespace com\openai\tools;

use util\data\Marshalling;

/**
 * Tools incorporate the builtins like `code_interpreter` but also custom
 * functions declared via `Functions` registry.
 *
 * @test  com.openai.unittest.ToolsTest
 * @see   https://platform.openai.com/docs/assistants/tools
 */
class Tools {
  public $selection= [];

  /**
   * Creates a new tools list from tools like `file_search` and `code_interpeter`
   * as well as user functions register in a `Functions` instance.
   *
   * @param  (string|[:var]|com.openai.tools.Functions)... $selected
   */
  public function __construct(...$selected) {
    foreach ($selected as $select) {
      $this->selection[]= is_string($select) ? ['type' => $select] : $select;
    }
  }

  /**
   * Returns marshalling
   *
   * @param  function(util.data.Definition): [:var] $definitions
   * @return util.data.Marshallling
   */
  public static function marshalling($definitions) {
    return (new Marshalling())
      ->mapping(Definition::class, $definitions)
      ->mapping(self::class, function($tools) {
        foreach ($tools->selection as $select) {
          if ($select instanceof Definitions) {
            yield from $select->schema();
          } else {
            yield $select;
          }
        }
      })
    ;
  }
}