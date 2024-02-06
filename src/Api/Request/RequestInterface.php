<?php

declare(strict_types=1);

namespace Api\Request;

interface RequestInterface
{
    public function method(): string;

    public function uri(): string;

    /**
     * @return array<string, string>
     */
    public function headers(): array;

    public function body(): ?array;
}
