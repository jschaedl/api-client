<?php

declare(strict_types=1);

namespace Api;

use Api\Request\RequestBodyEncoderInterface;
use Api\Request\RequestHandlerInterface;
use Api\Request\RequestInterface;
use Api\Response\Response;
use Api\Response\ResponseBodyDecoderInterface;
use Api\Response\ResponseHandlerInterface;
use Api\Response\ResponseInterface;
use Psr\Http\Client\ClientInterface as PsrClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Client implements ClientInterface
{
    private const UPPER = '_ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const LOWER = '-abcdefghijklmnopqrstuvwxyz';

    public function __construct(
        private readonly PsrClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $baseUri,
        private readonly ?RequestHandlerInterface $requestHandler = null,
        private readonly ?ResponseHandlerInterface $responseHandler = null,
        private readonly ?RequestBodyEncoderInterface $requestBodyEncoder = null,
        private readonly ?ResponseBodyDecoderInterface $responseBodyDecoder = null,
    ) {
    }

    public function request(RequestInterface $request): ResponseInterface
    {
        $baseUri = rtrim($this->baseUri, '/');
        $requestUri = ltrim($request->uri(), '/');

        // create psr7 request
        $psr7Request = $this->requestFactory->createRequest($request->method(), $baseUri.'/'.$requestUri);

        $requestBody = $request->body();

        if ($requestBody && $this->requestBodyEncoder) {
            throw new \RuntimeException('The request body is not empty, but no RequestBodyEncoder implementation was found. Did you forget to inject a RequestBodyEncoder?');
        }

        // encode request body
        if ($requestBody && $this->requestBodyEncoder) {
            $encodedRequestBody = $this->requestBodyEncoder->encode($requestBody);
            $stream = $this->streamFactory->createStream($encodedRequestBody);
            $psr7Request = $psr7Request->withBody($stream);
        }

        // execute request handler
        if ($this->requestHandler) {
            $psr7Request = $this->requestHandler->handle($psr7Request);
        }

        // override "global" request headers by request specific headers
        foreach ($request->headers() as $key => $value) {
            $key = strtr($key, self::UPPER, self::LOWER);
            $psr7Request = $psr7Request->withHeader($key, $value);
        }

        // send psr7 request
        $psr7Response = $this->httpClient->sendRequest($psr7Request);

        // execute response handler
        if ($this->responseHandler) {
            $psr7Response = $this->responseHandler->handle($psr7Response);
        }

        $body = $psr7Response->getBody()->getContents();

        // decode response body
        if (null !== $this->responseBodyDecoder) {
            $body = $this->responseBodyDecoder->decode($body);
        }

        $statusCode = $psr7Response->getStatusCode();
        $headers = $psr7Response->getHeaders();

        return new Response($body, $statusCode, $headers);
    }
}
