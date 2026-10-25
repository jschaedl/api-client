<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Request;

trait IsDelete
{
    public function method(): string
    {
        return Method::DELETE->value;
    }

    public function body(): null
    {
        return null;
    }
}
