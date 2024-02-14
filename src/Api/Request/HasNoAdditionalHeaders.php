<?php

declare(strict_types=1);

namespace Api\Request;

trait HasNoAdditionalHeaders
{
    public function headers(): array
    {
        return [];
    }
}
