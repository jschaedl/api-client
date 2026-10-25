<?php

declare(strict_types=1);

namespace Api\Request;

interface RequestInterface
{
    public function method(): string;

    public function uri(): string;

    /**
     * An array is encoded by a RequestBodyEncoderInterface, a string is sent as it is.
     *
     * @return array<mixed>|string|null
     */
    public function body(): array|string|null;
}
