OpenAI APIs for XP
==================

[![Build status on GitHub](https://github.com/xp-forge/openai/workflows/Tests/badge.svg)](https://github.com/xp-forge/openai/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_4plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/openai/version.svg)](https://packagist.org/packages/xp-forge/openai)

This library implements OpenAI APIs with a low-level abstraction approach, supporting their REST and realtime APIs, request and response streaming, function calling and TikToken encoding.

Quick start
-----------
Using the REST API, see https://platform.openai.com/docs/api-reference/making-requests

```php
use com\openai\rest\OpenAIEndpoint;
use util\cmd\Console;

$ai= new OpenAIEndpoint('https://'.getenv('OPENAI_API_KEY').'@api.openai.com/v1');

Console::writeLine($ai->api('/responses')->invoke([
  'model' => 'gpt-4o-mini',
  'input' => $prompt,
]));
```

Streaming
---------
The REST API can use server-sent events to stream responses, see https://platform.openai.com/docs/api-reference/streaming

```php
use com\openai\rest\OpenAIEndpoint;
use util\cmd\Console;

$ai= new OpenAIEndpoint('https://'.getenv('OPENAI_API_KEY').'@api.openai.com/v1');

$events= $ai->api('/responses')->stream([
  'model' => 'gpt-4o-mini',
  'input' => $prompt,
]);
foreach ($events as $type => $value) {
  Console::write('<', $type, '> ', $value);
}
Console::writeLine();
```

To access the result object, check for the *response.completed* event type and use its value. It contains the outuputs as well as model, filter results and usage information.

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

Instead of *encode()*, you can use *count()* to count the number of tokens.

Embeddings
----------
To create an embedding for a given text, use https://platform.openai.com/docs/guides/embeddings/what-are-embeddings

```php
use com\openai\rest\OpenAIEndpoint;
use util\cmd\Console;

$ai= new OpenAIEndpoint('https://'.getenv('OPENAI_API_KEY').'@api.openai.com/v1');

Console::writeLine($ai->api('/embeddings')->invoke([
  'input' => $text,
  'model' => 'text-embedding-3-small'],
));
```

Text to speech
--------------
To stream generate audio, use the API's *transmit()* method, which sends the given payload and returns the response. See https://platform.openai.com/docs/guides/text-to-speech/overview

```php
use com\openai\rest\OpenAIEndpoint;
use util\cmd\Console;

$ai= new OpenAIEndpoint('https://'.getenv('OPENAI_API_KEY').'@api.openai.com/v1');
$payload= [
  'input' => $input,
  'voice' => 'alloy',  // or: echo, fable, onyx, nova, shimmer
  'model' => 'tts-1',
];

$stream= $ai->api('/audio/speech')->transmit($payload)->stream();
while ($stream->available()) {
  Console::write($stream->read());
}
```

Speech to text
--------------
To convert audio into text, upload files via the API's *open()* method, which returns an *Upload* instance. See https://platform.openai.com/docs/guides/speech-to-text/overview

```php
use com\openai\rest\OpenAIEndpoint;
use io\File;
use util\cmd\Console;

$ai= new OpenAIEndpoint('https://'.getenv('OPENAI_API_KEY').'@api.openai.com/v1');
$file= new File($argv[1]);

$response= $ai->api('/audio/transcriptions')
  ->open(['model' => 'whisper-1'])
  ->transfer('file', $file->in(), $file->filename)
  ->finish()
;
Console::writeLine($response->value());
```

You can also stream uploads from *InputStream*s as follows:

```php
// ...setup code from above...

$upload= $ai->api('/audio/transcriptions')->open(['model' => 'whisper-1']);

$stream= $upload->stream('file', 'audio.mp3');
while ($in->available()) {
  $stream->write($in->read());
}
$response= $upload->finish();

Console::writeLine($response->value());
```

Tracing the calls
-----------------
REST API calls can be traced with the [logging library](https://github.com/xp-framework/logging):

```php
use com\openai\rest\OpenAIEndpoint;
use util\log\Logging;

$ai= new OpenAIEndpoint('https://'.getenv('OPENAI_API_KEY').'@api.openai.com/v1');
$ai->setTrace(Logging::all()->toConsole());

// ...perform API calls...
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

The *Param* annnotation may define a description and a [JSON schema type](https://json-schema.org/understanding-json-schema/reference):

* `#[Param('The name of the city')] $name`
* `#[Param(type: ['type' => 'string', 'enum' => ['C', 'F']])] $unit`

### Passing custom functions

Custom functions are registered in a `Functions` instance and passed via *tools* inside the payload.

```php
use com\openai\rest\OpenAIEndpoint;
use com\openai\tools\{Tools, Functions};

$functions= (new Functions())->register('weather', new Weather());

$ai= new OpenAIEndpoint('https://'.getenv('OPENAI_API_KEY').'@api.openai.com/v1');
$payload= [
  'model' => 'gpt-4o-mini',
  'tools' => new Tools($functions),
  'input' => [['type' => 'message', 'role' => 'user', 'content' => $content]],
];
```

### Invoking custom functions

If tool calls are requested by the LLM, invoke them and return to next completion cycle. See https://platform.openai.com/docs/guides/function-calling/configuring-parallel-function-calling

```php
use util\cmd\Console;

// ...setup code from above...

$calls= $functions->calls()->catching(fn($t) => $t->printStackTrace());
next: $result= $ai->api('/responses')->invoke($payload));

// If function calls are requested, invoke them and return to next response cycle
$invokations= false;
foreach ($result['output'] as $output) {
  if ('function_call' !== $output['type']) continue;

  $invokations= true;
  $return= $calls->call($call['name'], $call['arguments']);

  $payload['input'][]= $call;
  $payload['input'][]= [
    'type'    => 'function_call_output',
    'call_id' => $call['call_id'],
    'output'  => $return,
  ];
}
if ($invokations) goto next;

// Print out final result
Console::writeLine($result);
```

### Passing context

Functions can be passed a context as follows by annotating parameters with the *Context* annotation:

```php
use com\mongodb\{Collection, Document, ObjectId};
use com\openai\tools\{Context, Param};

// Declaration
class Memory {

  public function __construct(private Collection $facts) { }

  public function store(#[Context] Document $user, #[Param] string $fact): ObjectId {
    return $this->facts->insert(new Document(['owner' => $user->id(), 'fact' => $fact]))->id();
  }
}

// ...shortened for brevity...

$context= ['user' => $user];
$return= $calls->call($call['name'], $call['arguments'], $context);
```

Azure OpenAI
------------
These endpoints differ slightly in how they are invoked, which is handled by the *AzureAI* implementation. See https://learn.microsoft.com/en-us/azure/ai-services/openai/overview

```php
use com\openai\rest\AzureAIEndpoint;
use util\cmd\Console;

$ai= new AzureAIEndpoint(
  'https://'.getenv('AZUREAI_API_KEY').'@example.openai.azure.com/openai/deployments/mini',
  '2025-03-01-preview'
);

Console::writeLine($ai->api('/responses')->invoke([
  'model' => 'gpt-4o-mini',
  'input' => $prompt,
]));
```

Distributing requests
---------------------
The *Distributed* endpoint allows to distribute requests over multiple endpoints. The *ByRemainingRequests* class uses the `x-ratelimit-remaining-requests` header to determine the target. See https://platform.openai.com/docs/guides/rate-limits

```php
use com\openai\rest\{AzureAIEndpoint, Distributed, ByRemainingRequests};
use util\cmd\Console;

$endpoints= [
  new AzureAIEndpoint('https://...@r1.openai.azure.com/openai/deployments/mini', '2024-02-01'),
  new AzureAIEndpoint('https://...@r2.openai.azure.com/openai/deployments/mini', '2024-02-01'),
];

$ai= new Distributed($endpoints, new ByRemainingRequests());

Console::writeLine($ai->api('/responses')->invoke([
  'model' => 'gpt-4o-mini',
  'input' => $prompt,
]));
foreach ($endpoints as $i => $endpoint) {
  Console::writeLine('Endpoint #', $i, ': ', $endpoint->rateLimit());
}
```

For more complex load balancing, have a look at [this blog article using Azure API management](https://techcommunity.microsoft.com/t5/apps-on-azure-blog/openai-at-scale-maximizing-api-management-through-effective/ba-p/4240317)

Realtime API
------------
The realtime API allows streaming audio and/or text to and from language models, see https://platform.openai.com/docs/guides/realtime

```php
use com\openai\realtime\RealtimeApi;
use util\cmd\Console;

$api= new RealtimeApi('wss://api.openai.com/v1/realtime?model=gpt-4o-realtime-preview');
$session= $api->connect([
  'Authorization' => 'Bearer '.getenv('OPENAI_API_KEY'),
  'OpenAI-Beta'   => 'realtime=v1',
];
Console::writeLine($session);

// Send prompt
$api->transmit([
  'type' => 'conversation.item.create',
  'item' => [
    'type'    => 'message',
    'role'    => 'user',
    'content' => [['type' => 'input_text', 'text' => $message]],
  ]
]);

// Receive response(s)
$api->send(['type' => 'response.create', 'response' => ['modalities' => ['text']]]);
do {
  $event= $api->receive();
  Console::writeLine($event);
} while ('response.done' !== $event['type'] && 'error' !== $event['type']);

$api->close();
```

For Azure AI, the setup code is slightly different:

```php
use com\openai\realtime\RealtimeApi;
use util\cmd\Console;

$api= new RealtimeApi('wss://example.openai.azure.com/openai/realtime?'.
  '?api-version=2024-10-01-preview'.
  '&deployment=gpt-4o-realtime-preview'
);
$session= $api->connect(['api-key' => getenv('AZUREAI_API_KEY')]);
```

Completions API
---------------
To use the legacy (but industry standard) chat completions API, see https://platform.openai.com/docs/quickstart?api-mode=chat:

```php
use com\openai\rest\OpenAIEndpoint;
use util\cmd\Console;

$ai= new OpenAIEndpoint('https://'.getenv('OPENAI_API_KEY').'@api.openai.com/v1');

$flow= $ai->api('/chat/completions')->flow([
  'model'    => 'gpt-4o-mini',
  'messages' => [['role' => 'user', 'content' => $prompt]],
]);
$flow= $ai->api('/chat/completions')->flow($payload);
foreach ($flow->deltas() as $type => $delta) {
  Console::writeLine('<', $type, '> ', $delta);
}
Console::writeLine();
```

The result object is computed from the streamed deltas and can be retrieved by accessing *$flow->result()*.

See also
--------
* https://github.com/openai/tiktoken/
* https://github.com/openai/openai-python
* https://github.com/openai/openai-node
* https://github.com/Azure-Samples/azure-openai-reverse-proxy
* https://www.youtube.com/watch?v=i-oHvHejdsc - GPT Function calling in a nutshell