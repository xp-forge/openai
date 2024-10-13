OpenAI APIs for XP
==================

[![Build status on GitHub](https://github.com/xp-forge/openai/workflows/Tests/badge.svg)](https://github.com/xp-forge/openai/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_4plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/openai/version.svg)](https://packagist.org/packages/xp-forge/openai)

This library implements OpenAI APIs.

TikToken
--------
Encodes text to tokens. Download the [cl100k_base](https://openaipublic.blob.core.windows.net/encodings/cl100k_base.tiktoken) and [o200k_base](https://openaipublic.blob.core.windows.net/encodings/o200k_base.tiktoken) vocabularies first!

```php
use com\openai\{Encoding, TikTokenFilesIn};

$source= new TikTokenFilesIn('.');

// GPT 3.5, 4.0 => [9906, 4435, 0]
$tokens= Encoding::named('cl100k_base')->load($source)->encode('Hello World!');

// GPT o1, omni => [13225, 5922, 0]
$tokens= Encoding::named('o200k_base')->load($source)->encode('Hello World!');
```

See also
--------
* https://github.com/openai/tiktoken/