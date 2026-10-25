<?php

declare(strict_types=1);

namespace Api\Testing;

use Api\ClientInterface;
use Api\Exception\ResponseException;
use Api\Request\RequestInterface;
use Api\Response\Decoder\JsonResponseBodyDecoder;
use Api\Response\Response;
use Api\Response\ResponseInterface;

/**
 * A ClientInterface for testing code that uses an API client: answers each request with the
 * next queued response (or throws the next queued exception) and records every request.
 *
 * Queued Response objects with a 4xx/5xx status are thrown as ResponseException, like the real
 * Client does by default, so retry and error handling can be tested against it.
 *
 * @see MockClientTest
 */
final class MockClient implements ClientInterface
{
    /** @var list<ResponseInterface|\Throwable|\Closure(RequestInterface): ResponseInterface> */
    private array $queue = [];

    /** @var list<RequestInterface> */
    private array $requests = [];

    /**
     * @param iterable<ResponseInterface|\Throwable|\Closure(RequestInterface): ResponseInterface> $responses
     */
    public function __construct(iterable $responses = [])
    {
        $this->append(...$responses);
    }

    public static function json(mixed $body, int $statusCode = 200): Response
    {
        return new Response(
            json_encode($body, \JSON_THROW_ON_ERROR),
            $statusCode,
            ['Content-Type' => ['application/json']],
            new JsonResponseBodyDecoder(),
        );
    }

    public function append(ResponseInterface|\Throwable|\Closure ...$responses): self
    {
        foreach ($responses as $response) {
            $this->queue[] = $response;
        }

        return $this;
    }

    public function request(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        $next = array_shift($this->queue);

        if (null === $next) {
            throw new \LogicException(sprintf('No response queued for request #%d "%s %s".', \count($this->requests), $request->method(), $request->uri()));
        }

        if ($next instanceof \Throwable) {
            throw $next;
        }

        $response = $next instanceof \Closure ? $next($request) : $next;

        if ($response->statusCode() >= 400) {
            throw ResponseException::create($request, $response);
        }

        return $response;
    }

    /**
     * @return list<RequestInterface>
     */
    public function requests(): array
    {
        return $this->requests;
    }

    public function hasPendingResponses(): bool
    {
        return [] !== $this->queue;
    }
}
