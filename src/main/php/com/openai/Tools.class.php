<?php namespace com\openai;

use com\openai\tools\Functions;

/**
 * Tools
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
}