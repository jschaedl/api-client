<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Response;

use Jschaedl\ApiClient\Exception\DecodingException;

interface ResponseInterface
{
    public function statusCode(): int;

    /**
     * The decoded body, or the raw body when no decoder is configured or the body is empty.
     *
     * @throws DecodingException
     */
    public function body(): mixed;

    /**
     * The body exactly as received, e.g. for binary downloads.
     */
    public function rawBody(): string;

    /**
     * @return array<string, list<string>>
     */
    public function headers(): array;

    /**
     * The first value of a header, looked up case-insensitively.
     */
    public function header(string $name): ?string;
}
