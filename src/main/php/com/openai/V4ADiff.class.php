<?php namespace com\openai;

use io\streams\LinesIn;

class V4ADiff {
  public $instructions;

  /** @param array|io.streams.Reader|io.streams.InputStream|io.Channel|string $arg */
  public function __construct($arg) {
    if (is_array($arg)) {
      $this->instructions= $arg;
    } else {
      $this->instructions= [...$this->parse(new LinesIn($arg))];
    }
  }

  /** @param io.streams.LinesIn $lines */
  private function parse($lines): iterable {
    foreach ($lines as $line) {
      if ('*** Begin Patch' === $line) {
        continue;
      } else if ('' === $line) {
        yield ['type' => 'keep', 'value' => ''];
      } else if (' ' === $line[0]) {
        yield ['type' => 'keep', 'value' => substr($line, 1)];
      } else if ('+' === $line[0]) {
        yield ['type' => 'add', 'value' => substr($line, 1)];
      } else if ('-' === $line[0]) {
        yield ['type' => 'delete', 'value' => substr($line, 1)];
      } else if (0 === strncmp($line, '@@', 2)) {
        yield ['type' => 'anchor', 'value' => substr($line, 3)];
      } else if (2 === sscanf($line, "*** %[^:]: %[^\r]", $operation, $path)) {
        yield ['type' => strtolower($operation), 'value' => $path];
      } else if ('*** End Patch' === $line) {
        break;
      }
    }
  }

  public function apply($source) {
    $first= $this->instructions[0] ?? null;
    if (null === $first) {
      yield false => 'Nothing to apply';
      return;
    }

    $lines= $source instanceof LinesIn ? $source->getIterator() : (new LinesIn($source))->getIterator();
    foreach ($this->instructions as $instruction) {
      $line= $lines->current();
      switch ($instruction['type']) {
        case 'anchor':
          $anchor= $instruction['value'];
          if (empty($anchor)) break;

          // Find the anchor line
          while ($lines->valid()) {
            $line= $lines->current();
            yield true => $line;

            $lines->next();
            if (0 === strncmp($line, $anchor, strlen($anchor))) break 2;
          }

          yield false => 'Canot find anchor `'.$anchor.'` at line '.$lines->key();
          break;

        case 'add':
          yield true => $instruction['value'];
          break;

        case 'delete':
          if ($line !== $instruction['value']) {
            yield false => 'Cannot delete `'.$instruction['value'].'` at line '.$lines->key().', have `'.$line.'`';
            break;
          }

          $lines->next();
          break;

        case 'keep':
          if ($line !== $instruction['value']) {
            yield false => 'Cannot keep `'.$instruction['value'].'` at line '.$lines->key().', have `'.$line.'`';
            return;
          }

          $lines->next();
          yield true => $line;
          break;
      }
    }

    // Yield rest of file
    while ($lines->valid()) {
      yield true => $lines->current();
    }
  }
}