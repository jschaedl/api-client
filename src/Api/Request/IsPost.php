<?php

declare(strict_types=1);

namespace Api\Request;

trait IsPost
{
    public function method(): string
    {
        return Method::POST->value;
    }
}
