<?php

declare(strict_types=1);

namespace Api\Request;

trait HasNoRequestSpecificHeaders
{
    public function headers(): array
    {
        return [];
    }
}
