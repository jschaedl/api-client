<?php

declare(strict_types=1);

namespace Api\Response;

interface ResponseInterface
{
    public function statusCode(): int;

    public function body(): mixed;

    public function headers(): array;
}
