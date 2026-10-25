# Changelog

## 0.1.1

- Allow `webmozart/assert` 2.x (`^1.11||^2.0`).
- `RetryClient` takes an optional PSR-3 logger (`psr/log` is now required) and logs every retry,
  and every retry prevented by `maxDuration`, at info level. Entries never contain the query
  string or a body.
- `Clock\SymfonyClockAdapter` lets `RetryClient` and `GenericRetryStrategy` use a Symfony clock
  (requires `symfony/clock`).

## 0.1.0

PHP 8.2 to 8.5, method-aware retries, exceptions with status code and cause, string bodies,
per-request encoders, lazy response decoding and test doubles. See [UPGRADE-0.1.md](UPGRADE-0.1.md).
