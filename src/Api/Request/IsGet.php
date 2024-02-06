<?php

declare(strict_types=1);

namespace Api\Request;

trait IsGet
{
    public function method(): string
    {
        return Method::GET->value;
    }

    public function body(): ?array
    {
        return null;
    }
}
