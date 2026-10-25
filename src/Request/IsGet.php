<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Request;

trait IsGet
{
    public function method(): string
    {
        return Method::GET->value;
    }

    public function body(): null
    {
        return null;
    }
}
