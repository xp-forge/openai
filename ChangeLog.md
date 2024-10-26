OpenAI APIs for XP ChangeLog
========================================================================

## ?.?.? / ????-??-??

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