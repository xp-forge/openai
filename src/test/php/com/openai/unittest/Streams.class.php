<?php namespace com\openai\unittest;

use io\streams\{InputStream, MemoryInputStream};

trait Streams {

  /** Content completions */
  private function contentCompletions(): array {
    return [
      'data: {"choices":[{"delta":{"role":"assistant"}}]}',
      'data: {"choices":[{"delta":{"content":"Test"}}]}',
      'data: {"choices":[{"delta":{"content":"ed"}}]}',
      'data: [DONE]'
    ];
  }

  /** Tool call completions */
  private function toolCallCompletions(): array {
    return [
      'data: {"choices":[{"delta":{"role":"assistant"}}]}',
      'data: {"choices":[{"delta":{"tool_calls":[{"type":"function","function":{"name":"search","arguments":""}}]}}]}',
      'data: {"choices":[{"delta":{"tool_calls":[{"function":{"arguments":"{"}}]}}]}',
      'data: {"choices":[{"delta":{"tool_calls":[{"function":{"arguments":"}"}}]}}]}',
      'data: {"choices":[{"delta":{},"finish_reason":"function_call"}]}',
      'data: [DONE]'
    ];
  }

  /** Content response */
  private function contentResponse(): array {
    return [
      'event: response.created',
      'data: {"type":"response.created","response":{"id":"test"}}',
      '',
      'event: response.output_item.added',
      'data: {"type":"response.output_item.added","output_index":0,"item":{"type":"message"}}',
      '',
      'event: response.output_text.delta',
      'data: {"type":"response.output_text.delta","output_index":0,"content_index":0,"delta":"Test"}',
      '',
      'event: response.output_text.delta',
      'data: {"type":"response.output_text.delta","output_index":0,"content_index":0,"delta":"ed"}',
      '',
      'event: response.completed',
      'data: {"type":"response.completed","response":{"id":"test"}}'
    ];
  }

  /** Returns input */
  private function input(array $lines): InputStream {
    return new MemoryInputStream(implode("\n\n", $lines));
  }

  /** Maps deltas to a list of pairs */
  private function pairsOf(iterable $deltas): array {
    $r= [];
    foreach ($deltas as $field => $delta) {
      $r[]= [$field => $delta];
    }
    return $r;
  }
}