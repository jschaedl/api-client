<?php

declare(strict_types=1);

namespace Api\Request;

interface RequestBodyEncoderInterface
{
    /**
     * @param array<mixed> $requestBody
     */
    public function encode(array $requestBody): string;

    /**
     * Sent as Content-Type unless the request sets that header itself.
     */
    public function contentType(): string;
}
