<?php namespace com\openai\v4a;

use io\streams\LinesIn;
use lang\FormatException;

/** @test com.openai.unittest.PatchTest */
class Patch {
  public $operations;

  /** @param array|io.streams.Reader|io.streams.InputStream|io.Channel|string $input */
  public function __construct($input= []) {
    $this->operations= is_array($input) ? $input : self::operations($input);
  }

  /**
   * Parses operations from a given input. Supports enveloped diffs as well
   * as input consisting only of hunks as passed by the Agent SDK.
   *
   * @param  io.streams.Reader|io.streams.InputStream|io.Channel|string $input
   * @throws lang.FormatException for unexpected content
   */
  public static function operations($input, string $type= 'update'): array {
    $lines= $input instanceof LinesIn ? $input : new LinesIn($input);

    // Check for an envelope
    $it= $lines->getIterator();
    $first= $it->current();
    if ('*** Begin Patch' === $first) {
      $it->next();
      $operations= self::envelope($it);
      if ('*** End Patch' === $it->current()) return $operations;
    } else {
      $hunks= self::hunks($it);
      if (null === $it->current()) return [['type' => $type, 'path' => null, 'hunks' => $hunks]];
    }

    // Unexpected end of parsing
    throw new FormatException($it->valid() ? "Unexpected `{$it->current()}`" : 'Unexpected end of file');
  }

  /** Parses one or more hunks */
  private static function hunks($it) {
    static $hunk= ['anchors' => [], 'end' => false, 'instructions' => []];

    $ptr= $hunk;
    $hunks= [];
    $afterEnd= false;
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
      $operation= ['type' => $type, 'path' => $path];
      $it->next();

      // Check for `Move to` for `updates`.
      $line= $it->current();
      if (0 === strncmp($line, '*** Move to: ', 13)) {
        if ('update' !== $type) {
          throw new FormatException('`Move to` only allowed for update operations');
        }

        $operation['target']= substr($line, 13);
        $it->next();
      }

      // Read hunks until the next operation is encountered
      $operation['hunks']= self::hunks($it);
      $operations[]= $operation;
    }

    return $operations;
  }
}
