OpenAI APIs for XP
==================

[![Build status on GitHub](https://github.com/xp-forge/openai/workflows/Tests/badge.svg)](https://github.com/xp-forge/openai/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_4plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/openai/version.svg)](https://packagist.org/packages/xp-forge/openai)

This library implements OpenAI APIs with a low-level abstraction approach.

TikToken
--------
Encodes text to tokens. Download the vocabularies [cl100k_base](https://openaipublic.blob.core.windows.net/encodings/cl100k_base.tiktoken) (used for GPT-3.5 and GPT-4.0) and [o200k_base](https://openaipublic.blob.core.windows.net/encodings/o200k_base.tiktoken) (used for Omni and O1) first!

```php
use com\openai\{Encoding, TikTokenFilesIn};

$source= new TikTokenFilesIn('.');

// By name => [9906, 4435, 0]
$tokens= Encoding::named('cl100k_base')->load($source)->encode('Hello World!');

// By model => [13225, 5922, 0]
$tokens= Encoding::for('omni')->load($source)->encode('Hello World!');
```

Completions
-----------
Using the REST API, see https://platform.openai.com/docs/api-reference/making-requests

```php
use com\openai\rest\OpenAIEndpoint;
use util\cmd\Console;

$ai= new OpenAIEndpoint('https://'.getenv('OPENAI_API_KEY').'@api.openai.com/v1');
$payload= [
  'model'    => 'gpt-4o-mini',
  'messages' => [['role' => 'user', 'content' => $prompt]],
];

Console::writeLine($ai->api('/chat/completions')->invoke($payload));
```

Streaming
---------
The REST API can use server-sent events to stream responses, see https://platform.openai.com/docs/api-reference/streaming

```php
use com\openai\rest\OpenAIEndpoint;
use util\cmd\Console;

$ai= new OpenAIEndpoint('https://'.getenv('OPENAI_API_KEY').'@api.openai.com/v1');
$payload= [
  'model'    => 'gpt-4o-mini',
  'messages' => [['role' => 'user', 'content' => $prompt]],
];

$stream= $ai->api('/chat/completions')->stream($payload);
foreach ($stream->deltas('content') as $delta) {
  Console::write($delta);
}
Console::writeLine();
```

Embeddings
----------
To create an embedding for a given text, use https://platform.openai.com/docs/guides/embeddings/what-are-embeddings

```php
use util\cmd\Console;
use com\openai\rest\OpenAIEndpoint;

$ai= new OpenAIEndpoint('https://'.getenv('OPENAI_API_KEY').'@api.openai.com/v1');

Console::writeLine($ai->api('/embeddings')->invoke([
  'input' => $text,
  'model' => 'text-embedding-3-small'],
));
```

Tool calls
----------
There are two types of tools: Built-ins like *file_search* and *code_interpreter* (available [in the assistants API](https://platform.openai.com/docs/assistants/tools)) as well as custom functions, see https://platform.openai.com/docs/guides/function-calling 

### Defining functions

Custom functions map to instance methods in a class:

```php
use com\openai\tools\Param;
use webservices\rest\Endpoint;

class Weather {
  private $endpoint;

  public function __construct(string $base= 'https://wttr.in/') {
    $this->endpoint= new Endpoint($base);
  }

  public function in(#[Param] string $city): string {
    return $this->endpoint->resource('/{0}?0mT', [$city])->get()->content(); 
  }
}
```

### Passing custom functions

Custom functions are registered in a `Functions` instance and passed via *tools* inside the payload.

```php
use com\openai\rest\OpenAIEndpoint;
use com\openai\Tools;
use com\openai\tools\Functions;
use util\cmd\Console;

$functions= (new Functions())->register('weather', new Weather());

$ai= new OpenAIEndpoint('https://'.getenv('OPENAI_API_KEY').'@api.openai.com/v1');
$payload= [
  'model'    => 'gpt-4o-mini',
  'tools'    => new Tools($functions),
  'messages' => [['role' => 'user', 'content' => $prompt]],
];
```

### Invoking custom functions

If tool calls are requested by the LLM, invoke them and return to next completion cycle. Arguments and return values are encoded as *JSON*. See https://platform.openai.com/docs/guides/function-calling/configuring-parallel-function-calling

```php
use lang\Throwable;

// ...setup code from above...

complete: $result= $ai->api('/chat/completions')->invoke($payload));

// If tool calls are requested, invoke them and return to next completion cycle
if ('tool_calls' === ($result['choices'][0]['finish_reason'] ?? null)) {
  $payload['messages'][]= $result['choices'][0]['message'];
  
  foreach ($result['choices'][0]['message']['tool_calls'] as $call) {
    try {
      $return= $functions->invoke(
        $call['function']['name'],
        json_decode($call['function']['arguments'], true)
      );
    } catch (Throwable $t) {
      $t->printStackTrace();
      $return= ['error' => $t->compoundMessage()];
    }

    $payload['messages'][]= [
      'role'         => 'tool',
      'tool_call_id' => $call['id'],
      'content'      => json_encode($return),
    ];
  }

  goto complete;
}

// Print out final result
Console::writeLine($result);
```

Azure OpenAI
------------
*Coming soon*

Realtime API
------------
*Coming soon*

See also
--------
* https://github.com/openai/tiktoken/