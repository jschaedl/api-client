# Upgrade from 0.0.1 to 0.1.0

0.1.0 supports PHP 8.2 to 8.5 and reworks retries and error handling. These changes need action.

## Requirements

- PHP 8.2 to 8.5.
- `psr/http-factory` 1.1 or newer (1.0 declares implicitly nullable parameters, which PHP 8.4
  deprecates).
- `psr/clock` is a new dependency.

## Errors are thrown by default

`Client` now throws on every 4xx/5xx response: `ClientException` / `ServerException`, both
extending `ResponseException`. Network failures throw `TransportException` instead of the PSR-18
exception. Every exception implements `Api\Exception\ApiExceptionInterface`.

- To keep getting error responses returned, pass `throwOnErrorStatus: false`.
- `ResponseException` (and its subclasses) are now built from the request and response:
  `new ClientException($request, $response)` replaces `new ClientException(404, 'Not Found')`.
  `getCode()` is the HTTP status.

## Retries

`RetryTrait` is gone. `RetryClient` takes a `RetryStrategyInterface`:

```php
// 0.0.1
new RetryClient($client, 3, true);

// 0.1.0
new RetryClient($client, new GenericRetryStrategy(), maxRetries: 3);
```

Behaviour changes:

- 5xx and network failures are retried for **idempotent methods only**. 0.0.1 retried a 5xx for
  every method, POST included.
- 429 is retried for every method. `Retry-After` is honoured.
- `maxRetries` now counts retries: `maxRetries: 3` means up to 4 requests. 0.0.1 counted attempts.
- Waiting goes through `Api\Clock\ClockInterface`; use `MockClock` in tests instead of overriding
  `getWaitTimeFactor()`.

## Request bodies

- `RequestInterface::body()` returns `array|string|null`. A string is sent as it is.
- `RequestBodyEncoderInterface` gained `contentType()`. The client sets that `Content-Type` unless
  the request sets the header itself, so `AddHeaderHandler('Content-Type', 'application/json')`
  is no longer needed.
- `JsonRequestBodyEncoder` no longer escapes slashes and unicode by default. Pass flags to the
  constructor for the old output: `new JsonRequestBodyEncoder(0)`.

## Responses

- `ResponseInterface` gained `rawBody()` and `header()`; custom implementations must add them.
- The body is decoded lazily on the first `body()` call. Decoding errors throw
  `DecodingException` there, not inside `Client::request()`.
- `ResponseBodyDecoderInterface::decode()` returns `mixed`. `JsonResponseBodyDecoder` no longer
  casts the result to an array.
- `ResponseHandlerInterface::handle()` receives the request as a second argument and works on
  `ResponseInterface` instead of `Response`. Handlers now run before the status check.
