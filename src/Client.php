<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient;

use Jschaedl\ApiClient\Exception\ResponseException;
use Jschaedl\ApiClient\Exception\TransportException;
use Jschaedl\ApiClient\Request\BodyEncoderAwareRequestInterface;
use Jschaedl\ApiClient\Request\HeadersAwareRequestInterface;
use Jschaedl\ApiClient\Request\RequestBodyEncoderInterface;
use Jschaedl\ApiClient\Request\RequestHandlerInterface;
use Jschaedl\ApiClient\Request\RequestInterface;
use Jschaedl\ApiClient\Response\Response;
use Jschaedl\ApiClient\Response\ResponseBodyDecoderInterface;
use Jschaedl\ApiClient\Response\ResponseHandlerInterface;
use Jschaedl\ApiClient\Response\ResponseInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as PsrClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface as Psr7RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Timeouts are a property of the PSR-18 client you pass in (PSR-18 has no per-request
 * options): configure them there, e.g. `new Psr18Client(HttpClient::create(['timeout' => 10]))`.
 *
 * @see ClientTest
 */
final readonly class Client implements ClientInterface
{
    public function __construct(
        private PsrClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private string $baseUri,
        private ?RequestHandlerInterface $requestHandler = null,
        private ?ResponseHandlerInterface $responseHandler = null,
        private ?RequestBodyEncoderInterface $requestBodyEncoder = null,
        private ?ResponseBodyDecoderInterface $responseBodyDecoder = null,
        private bool $throwOnErrorStatus = true,
    ) {
    }

    public function request(RequestInterface $request): ResponseInterface
    {
        if ($this->requestHandler instanceof RequestHandlerInterface) {
            $request = $this->requestHandler->handle($request);
        }

        $psr7Request = $this->createPsr7Request($request);

        try {
            $psr7Response = $this->httpClient->sendRequest($psr7Request);
            // the body may break off mid-read, which is a transport failure as well
            $rawBody = (string) $psr7Response->getBody();
        } catch (ClientExceptionInterface|\RuntimeException $exception) {
            throw new TransportException($request, $exception);
        }

        $headers = [];
        foreach ($psr7Response->getHeaders() as $name => $values) {
            $headers[(string) $name] = array_values($values);
        }

        $response = new Response($rawBody, $psr7Response->getStatusCode(), $headers, $this->responseBodyDecoder);

        if ($this->responseHandler instanceof ResponseHandlerInterface) {
            $response = $this->responseHandler->handle($response, $request);
        }

        if ($this->throwOnErrorStatus && $response->statusCode() >= 400) {
            throw ResponseException::create($request, $response);
        }

        return $response;
    }

    private function createPsr7Request(RequestInterface $request): Psr7RequestInterface
    {
        $uri = rtrim($this->baseUri, '/').'/'.ltrim($request->uri(), '/');
        $psr7Request = $this->requestFactory->createRequest($request->method(), $uri);

        $body = $request->body();

        if (\is_array($body)) {
            $encoder = $this->resolveEncoder($request);
            $psr7Request = $psr7Request
                ->withHeader('Content-Type', $encoder->contentType())
                ->withBody($this->streamFactory->createStream($encoder->encode($body)));
        } elseif (\is_string($body)) {
            $psr7Request = $psr7Request->withBody($this->streamFactory->createStream($body));
        }

        // request specific headers win over everything set above, Content-Type included
        if ($request instanceof HeadersAwareRequestInterface) {
            foreach ($request->headers() as $header) {
                $psr7Request = $psr7Request->withHeader($header->name(), $header->value());
            }
        }

        return $psr7Request;
    }

    private function resolveEncoder(RequestInterface $request): RequestBodyEncoderInterface
    {
        $encoder = $request instanceof BodyEncoderAwareRequestInterface ? $request->bodyEncoder() : null;
        $encoder ??= $this->requestBodyEncoder;

        if (!$encoder instanceof RequestBodyEncoderInterface) {
            throw new \LogicException('The request body is an array, but no RequestBodyEncoder was found. Inject one into the Client or set one on the request.');
        }

        return $encoder;
    }
}
