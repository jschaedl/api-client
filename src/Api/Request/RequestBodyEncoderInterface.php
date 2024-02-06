<?php

declare(strict_types=1);

namespace Api\Request;

interface RequestBodyEncoderInterface
{
    public function encode(array $requestBody): string;
}
