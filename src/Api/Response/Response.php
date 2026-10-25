<?php

declare(strict_types=1);

namespace Api\Response;

use Api\Exception\DecodingException;

/**
 * @see ResponseTest
 */
final class Response implements ResponseInterface
{
    private bool $decoded = false;

    private mixed $body = null;

    /**
     * @param array<string, list<string>> $headers
     */
    public function __construct(
        private readonly string $rawBody,
        private readonly int $statusCode,
        private readonly array $headers = [],
        private readonly ?ResponseBodyDecoderInterface $decoder = null,
    ) {
    }

    /**
     * Decoded on first access, so a response that is never read (or only read raw) is never decoded.
     */
    public function body(): mixed
    {
        if (!$this->decoder instanceof ResponseBodyDecoderInterface || '' === $this->rawBody) {
            return $this->rawBody;
        }

        if (!$this->decoded) {
            try {
                $this->body = $this->decoder->decode($this->rawBody);
            } catch (\Throwable $exception) {
                throw new DecodingException($this->statusCode, $exception);
            }

            $this->decoded = true;
        }

        return $this->body;
    }

    public function rawBody(): string
    {
        return $this->rawBody;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $headerName => $values) {
            if (0 === strcasecmp($headerName, $name)) {
                return $values[0] ?? null;
            }
        }

        return null;
    }
}
