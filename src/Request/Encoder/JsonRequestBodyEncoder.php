<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Request\Encoder;

use Jschaedl\ApiClient\Request\RequestBodyEncoderInterface;

final readonly class JsonRequestBodyEncoder implements RequestBodyEncoderInterface
{
    public function __construct(
        private int $flags = \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE,
    ) {
    }

    public function encode(array $requestBody): string
    {
        return json_encode($requestBody, $this->flags | \JSON_THROW_ON_ERROR);
    }

    public function contentType(): string
    {
        return 'application/json';
    }
}
