<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Request;

trait IsPost
{
    public function method(): string
    {
        return Method::POST->value;
    }
}
