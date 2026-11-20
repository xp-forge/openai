<?php namespace com\openai\v4a;

use io\streams\StringReader;
use lang\{FormatException, IllegalStateException};

/** @test com.openai.unittest.PatchTest */
class Patch {

  /** @type com.openai.v4a.Operation[] */
  public $operations;

  /** @param array|string|io.streams.InputStream|io.Channel $input */
  public function __construct($input= []) {
    $this->operations= is_array($input) ? $input : self::operations($input);
  }

  /** Parses one or more hunks */
  private static function hunks($it) {
    static $hunk= ['anchors' => [], 'end' => false, 'instructions' => []];

    $ptr= $hunk;
    $hunks= [];
    while ($it->valid()) {
      $line= $it->current();

      if ('' === $line) {
        $ptr['instructions'][]= ['type' => 'keep', 'value' => ''];
      } else if (' ' === $line[0]) {
        $ptr['instructions'][]= ['type' => 'keep', 'value' => substr($line, 1)];
      } else if ('+' === $line[0]) {
        $ptr['instructions'][]= ['type' => 'add', 'value' => substr($line, 1)];
      } else if ('-' === $line[0]) {
        $ptr['instructions'][]= ['type' => 'delete', 'value' => substr($line, 1)];
      } else if ('@@' === $line || 0 === strncmp($line, '@@ ', 3)) {

        // If instructions are present, group into previous hunk
        anchor: if ($ptr['instructions']) {
          $hunks[]= $ptr;
          $ptr= $hunk;
        }

        $ptr['anchors'][]= substr($line, 3);
      } else if ('*** End of File' === $line) {
        if (empty($ptr['instructions'])) throw new FormatException('`End of File` without instructions');
        $ptr['end']= true;

        // End of File may be followed by empty lines and an anchor
        while ($it->valid()) {
          $it->next();
          $line= $it->current();
          if ('@@' === $line || 0 === strncmp($line, '@@ ', 3)) goto anchor;
          if ('' !== $line) break;
        }
        break;
      } else if (0 === strncmp($line, '*** ', 4)) {
        break;
      } else {
        throw new FormatException("Invalid line `{$line}`");
      }

      $it->next();
    }

    if ($ptr['instructions']) $hunks[]= $ptr;
    return $hunks;
  }

  /** Parses an envelope */
  private static function envelope($it) {
    $operations= [];
    while ($it->valid()) {
      $line= $it->current();
      if ('*** End Patch' === $line) {
        break;
      } else if (2 !== sscanf($line, "*** %[^:]: %[^\r]", $operation, $path)) {
        throw new FormatException("Unexpected `{$it->current()}`");
      }

      // Parse operation, which is one of `add`, `update` or `delete`.
      $type= match (strtolower($operation)) {
        'add file'    => 'add',
        'update file' => 'update',
        'delete file' => 'delete',
        default => throw new FormatException("Unknown operation `{$operation}`")
      };
      $operation= new Operation($type, $path);
      $it->next();

      // Check for `Move to` for `updates`.
      $line= $it->current();
      if (0 === strncmp($line, '*** Move to: ', 13)) {
        if ('update' !== $type) {
          throw new FormatException('`Move to` only allowed for update operations');
        }

        $operation->target= substr($line, 13);
        $it->next();
      }

      // Read hunks until the next operation is encountered
      $operation->hunks= self::hunks($it);
      $operations[]= $operation;
    }

    return $operations;
  }

  /**
   * Parses operations from a given input. Supports enveloped diffs as well
   * as input consisting only of hunks as passed by the Agent SDK.
   *
   * @param  string|io.streams.InputStream|io.Channel $input
   * @return com.openai.Operation[]
   * @throws lang.FormatException for unexpected content
   */
  public static function operations($input, string $type= 'update'): array {
    $reader= new StringReader($input);
    $it= (function() use($reader) {
      while (null !== ($line= $reader->readLine())) {
        yield $line;
      }
    })();

    // Check for an envelope
    $first= $it->current();
    if ('*** Begin Patch' === $first) {
      $it->next();
      $operations= self::envelope($it);
      if ('*** End Patch' === $it->current()) return $operations;
    } else {
      $hunks= self::hunks($it);
      if (null === $it->current()) return [new Operation($type, null, $hunks)];
    }

    // Unexpected end of parsing
    throw new FormatException($it->valid() ? "Unexpected `{$it->current()}`" : 'Unexpected end of file');
  }

  /** Returns the number of operations in this patch */
  public function size(): int { return sizeof($this->operations); }

  /**
   * Returns the only operation, throwing an exception if there is
   * not exactly 1 operation.
   *
   * @throws lang.IllegalStateException
   */
  public function single(): Operation {
    if (1 === sizeof($this->operations)) return $this->operations[0];

    throw new IllegalStateException('Unexpected number of operations: '.sizeof($this->operations));
  }
}