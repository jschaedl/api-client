# api-client

<p>
    <a href="https://github.com/jschaedl/api-client/actions">
        <img src="https://github.com/jschaedl/api-client/actions/workflows/ci.yaml/badge.svg" alt="ci status">
    </a>
    <img src="https://img.shields.io/badge/php-8.2%20to%208.5-lightblue.svg" alt="php-8.2 to 8.5">
    <a href="https://packagist.org/packages/jschaedl/api-client">
        <img src="https://poser.pugx.org/jschaedl/api-client/v/stable" alt="latest version">
    </a>
    <a href="https://packagist.org/packages/jschaedl/api-client">
        <img src="https://poser.pugx.org/jschaedl/api-client/downloads" alt="total downloads">
    </a>
    <img src="https://poser.pugx.org/jschaedl/api-client/license" alt="license">
</p>

Are you tired of spending countless hours writing repetitive boilerplate code to create API clients? 
Say goodbye to the hassle and welcome `jschaedl/api-client`, the library that will help you to build robust API clients 
effortlessly. It provides a simple, yet powerful foundation of classes to make building API clients 
fun again.

## Key Benefits

**Faster Development:** With `jschaedl/api-client`, you can create API clients in minutes, thanks to the provided classes. 
No more wasting time on mundane tasks!

**Versatility:** `jschaedl/api-client` supports a wide range of APIs, making it a versatile solution for various projects. 
Whether you're working with RESTful, SOAP, or GraphQL APIs, it got you covered.

**Customization:** Tailor API clients to your specific needs with ease. `jschaedl/api-client` provides a flexible 
framework that allows developers to customize requests, responses, and error handling effortlessly.

**Standards:** `jschaedl/api-client` follows PSR-7 (HTTP message interfaces), PSR-17 (HTTP factoring interfaces), and 
PSR-18 (HTTP client interfaces) to ensure compatibility and interoperability with industry-approved practices.

## When you should use this library?

**No Existing SDK:** When there's no SDK available for the API you want to consume, and you find yourself needing to 
implement an API client from scratch, `jschaedl/api-client` is your go-to solution.

**Selective Endpoint Calls:** In cases where there is an SDK for the API you want to consume, but you only require
specific endpoints and don't want to deal with the overhead of the entire bloated SDK, `jschaedl/api-client` steps in.

**Avoiding 3rd Party SDK Dependencies:** Tired of integrating 3rd party SDKs that bring along a cascade of dependencies?
Opt for `jschaedl/api-client` to sidestep the hassle.

---

## Installation

To install `jschaedl/api-client` via composer use

```bash
composer require jschaedl/api-client
```

---

## Examples

Runnable examples live in [`examples/`](examples) (Shopware 5/6, MobilePay, a generic request).

```php
use Api\Client;
use Api\Request\Encoder\JsonRequestBodyEncoder;
use Api\Request\Handler\AddHeaderHandler;
use Api\Request\Method;
use Api\Request\Request;
use Api\Response\Decoder\JsonResponseBodyDecoder;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

// timeouts belong to the PSR-18 client: PSR-18 has no per-request options
$psr18 = new Psr18Client(HttpClient::create(['timeout' => 10, 'max_duration' => 30]));

$client = new Client(
    $psr18, $psr18, $psr18,
    'https://api.example.com/v1',
    requestHandler: new AddHeaderHandler('Authorization', 'Bearer '.$token),
    requestBodyEncoder: new JsonRequestBodyEncoder(),
    responseBodyDecoder: new JsonResponseBodyDecoder(),
);

$response = $client->request(new Request(Method::POST, 'orders', ['sku' => 'ABC-1']));
$response->statusCode(); // 201
$response->body();       // ['id' => 42, ...]
```

---

## Features

### Client

`Client` turns a `RequestInterface` into a PSR-7 request, sends it with any PSR-18 client and
returns a `ResponseInterface`. Request URIs are relative to the base URI.

### Request and Response

`Request` carries method, URI, body and headers. The body is

- an **array**: encoded by the request's own encoder, or the client's (see below);
- a **string**: sent exactly as it is (binary uploads, pre-encoded payloads);
- **null**: no body.

`Response` keeps the raw body (`rawBody()`) and decodes it lazily on the first `body()` call, so a
PDF download or an HTML error page is never run through a JSON decoder unless you ask for it.
`header('retry-after')` looks a header up case-insensitively.

### Error handling

By default the client throws on every 4xx/5xx response. All exceptions implement
`Api\Exception\ApiExceptionInterface`:

| Exception | When | `getCode()` |
| --- | --- | --- |
| `ClientException` | 4xx response | HTTP status |
| `ServerException` | 5xx response | HTTP status |
| `TransportException` | no complete response (DNS, connect, TLS, timeout, broken body) | 0 |
| `DecodingException` | `body()` cannot decode the response | HTTP status |

Messages name the method and path only (`HTTP 422 returned for "POST members".`). They never contain
the response body (error responses often echo personal data back) or the query string (some APIs
take credentials there). The request, the response and the original exception stay reachable via
`request()`, `response()` and `getPrevious()`.

Pass `throwOnErrorStatus: false` to get error responses returned instead.

### Request and Response Handlers

Request handlers change a request before it is sent (`AddHeaderHandler`, `AddAccessTokenHandler`,
`ChainRequestHandler`). Response handlers see every response **before** the status check, so they
can turn an expected error into a domain exception:

```php
final class MemberNotFoundHandler implements ResponseHandlerInterface
{
    public function handle(ResponseInterface $response, RequestInterface $request): ResponseInterface
    {
        if (404 === $response->statusCode()) {
            throw new MemberNotFound();
        }

        return $response;
    }
}
```

### Request/Response Body Encoder/Decoder

`JsonRequestBodyEncoder` and `FormRequestBodyEncoder` (`application/x-www-form-urlencoded`) set
their own `Content-Type`; a header set on the request wins. A request can bring its own encoder,
e.g. a form-encoded OAuth token call on an otherwise JSON API:

```php
new Request(Method::POST, 'oauth/token', ['grant_type' => 'client_credentials'], bodyEncoder: new FormRequestBodyEncoder());
```

### Custom Requests and Responses

Extend `Request`, or implement `RequestInterface` with the `IsGet`, `IsPost`, `IsPut`, `IsPatch`
and `IsDelete` traits. See `examples/shopware6.php` for typed request and response classes.

### RetryClient

`RetryClient` decorates any `ClientInterface` and sends a failed request again when its
`RetryStrategyInterface` allows it. `GenericRetryStrategy` retries only what was provably not
processed, or what is safe to repeat. A POST that failed with a 5xx or a timeout may have been
processed anyway, and resending it can create a second order or invoice. The defaults:

| Failure | GET, HEAD, OPTIONS, PUT, DELETE | POST, PATCH |
| --- | --- | --- |
| 429 Too Many Requests | retried | retried (rate limiters reject before processing) |
| 423, 425, 500, 502, 503, 504 | retried | not retried |
| Network error (timeout, DNS, connection reset) | retried | not retried |
| Any other status | not retried | not retried |

Delays grow exponentially with jitter and are capped. `Retry-After` (seconds or HTTP-date) is
honoured; if it asks for longer than `maxDelay`, the request is not retried. `maxDuration` bounds
the whole sequence, waits included.

```php
use Api\RetryClient;
use Api\Retry\GenericRetryStrategy;

$client = new RetryClient(
    $client,
    new GenericRetryStrategy(
        statusCodes: [409 => GenericRetryStrategy::IDEMPOTENT_METHODS, 429], // codes with methods = only those methods
        delay: 20.0,       // seconds before the first retry
        multiplier: 1.0,   // flat: one retry per rate-limit window
        maxDelay: 20.0,
    ),
    maxRetries: 3,
    maxDuration: 70.0,
);
```

### Testing with Mocks

`Api\Testing\MockClient` answers requests from a queue and records them, which is handy for
testing code that uses a client. Error responses are thrown as `ResponseException`, like the real client
does:

```php
$client = new MockClient([
    MockClient::json(['id' => 7]),
    MockClient::json(['error' => 'not found'], 404),
]);

// ... exercise your code ...

self::assertCount(2, $client->requests());
```

`Api\Clock\MockClock` advances its time on `sleep()` instead of waiting. Pass it to `RetryClient`
and `GenericRetryStrategy` to test retries without delays; `sleeps()` returns the waits.

To test the `Client` itself without network access, wrap Symfony's `MockHttpClient` in a
`Psr18Client`.

---

## FAQ

**How can I add custom headers to requests?**

Globally with `AddHeaderHandler` (combine several with `ChainRequestHandler`), or per request with
`$request->addHeader('X-Foo', 'bar')`. Request headers win.

**How can I send authentication information along with a request?**

Use `AddAccessTokenHandler` with your own `AccessTokenProviderInterface` (see
`examples/shopware6.php` for a cached client-credentials token), or `AddHeaderHandler` for static
keys.

**How can I use a Client in my tests?**

See [Testing with Mocks](#testing-with-mocks).

**How do I upgrade from 0.0.1?**

See [UPGRADE-0.1.md](UPGRADE-0.1.md).

---

## Author

[Jan Schädlich](http://janschaedlich.de)

[Contributors](https://github.com/jschaedl/api-client/graphs/contributors)

---

## How to Contribute

Please read our [contributing guidelines](/CONTRIBUTING.md).

---

## License

MIT License
