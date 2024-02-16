<?php

declare(strict_types=1);

namespace Api\Request;

trait IsDelete
{
    public function method(): string
    {
        return Method::DELETE->value;
    }

    public function body(): ?array
    {
        return null;
    }
}
