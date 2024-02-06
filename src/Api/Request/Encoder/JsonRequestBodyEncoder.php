<?php

declare(strict_types=1);

namespace Api\Request\Encoder;

use Api\Request\RequestBodyEncoderInterface;

final class JsonRequestBodyEncoder implements RequestBodyEncoderInterface
{
    public function encode(array $requestBody): string
    {
        return json_encode($requestBody, JSON_THROW_ON_ERROR);
    }
}
