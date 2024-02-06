<?php

declare(strict_types=1);

namespace Api\Request;

trait IsPatch
{
    public function method(): string
    {
        return Method::PATCH->value;
    }
}
