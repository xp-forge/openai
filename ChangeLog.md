OpenAI APIs for XP ChangeLog
========================================================================

## ?.?.? / ????-??-??

## 0.12.0 / 2026-02-21

* Merged PR #23: Support accessing Azure AI V1 API without api-version
  (@theid)

## 0.11.0 / 2026-01-24

* Merged PR #22: Allow specifying parameters in the RealtimeApi constructor
  (@thekid)

## 0.10.1 / 2026-01-17

* Fixed "Using null as an array offset is deprecated" warnings - @thekid
* Added PHP 8.5 and 8.6 to the test matrix - @thekid

## 0.10.0 / 2025-08-16

* Made compatible with `xp-forge/rest-client` 6.0+ - @thekid

## 0.9.0 / 2025-04-14

* **Heads up:** To use streaming with the legacy completions API, you
  need to use *flow()* instead of *stream()*!
  (@thekid)
* Merged PR #21: Implement responses API streaming, see issue #20 and
  https://platform.openai.com/docs/api-reference/responses
  (@thekid)
* Added *ping()* method to `com.openai.realtime.RealtimeApi` class.
  (@thekid)
* Added optional parameter *timeout* to receive() and transmit() methods
  (@thekid)

## 0.8.0 / 2024-11-02

* Made it possible to supply organization and project in OpenAI API URI
  (@thekid)
* Added `RealtimeApi::socket()` to access the underlying network socket
  (@thekid)
* Merged PR #17: Move the `Tools` class to the com.openai.tools package
  (@thekid)

## 0.7.0 / 2024-11-01

* Merged PR #16: Refactor implementation to unify tools usage for REST
  and realtime APIs
  (@thekid)
* Merged PR #15: Implement realtime API. This implements issue #8 in a
  new `com.openai.realtime` package
  (@thekid)

## 0.6.0 / 2024-10-27

* Merged PR #9: Support uploading files, e.g. for transcribing audio.
  (@thekid)

## 0.5.0 / 2024-10-26

* Merged PR #13: Use marshalling for function calls. This way functions
  can have their arguments converted from and return values converted 
  to JSON primitives 
  (@thekid)
* Merged PR #12: Wrap function calling in `Calls` API. This simplifies
  function calling by handling JSON de- and encoding as well as supplying
  a default error handling mechanism
  (@thekid)

## 0.4.0 / 2024-10-26

* Merged PR #11: Add distribution strategy and the `ByRemainingRequests`
  implementation
  (@thekid)

## 0.3.0 / 2024-10-22

* Merged PR #10: Add endpoint implementation which will distribute API
  requests based on rate limits; see also #7
  (@thekid)

## 0.2.0 / 2024-10-20

* Included usage in streaming responses using `{"include_usage": true}`,
  implementing feature requested in #5
  (@thekid)
* Merged PR #6: Add `Api::transmit()` to invoke API and return response
  (@thekid)

## 0.1.0 / 2024-10-19

* Added support for optional organization and project identifiers, see
  https://platform.openai.com/docs/api-reference/authentication
  (@thekid)
* Merged PR #4: Implement Azure AI endpoints, which differ in the way
  they pass the API key and that they need an API version.
  (@thekid)
* Merged PR #3: Implement function calling and assistant tools support
  (@thekid)
* Added tracing capabilities to `com.openai.rest.OpenAIEndpoint` class
  (@thekid)
* Merged PR #2: Implement REST API - including support for streaming
  (@thekid)
* Merged PR #1: Add integration tests for `cl100k_base` and `o200k_base`
  (@thekid)