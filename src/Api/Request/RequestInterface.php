<?php

declare(strict_types=1);

namespace Api\Request;

interface RequestInterface
{
    public function method(): string;

    public function uri(): string;

    public function body(): ?array;
}
