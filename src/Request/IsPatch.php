<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Request;

trait IsPatch
{
    public function method(): string
    {
        return Method::PATCH->value;
    }
}
