<?php

declare(strict_types=1);

namespace Api\Request;

trait IsPut
{
    public function method(): string
    {
        return Method::PUT->value;
    }
}
