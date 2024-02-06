<?php

declare(strict_types=1);

namespace Api\Response;

interface ResponseBodyDecoderInterface
{
    public function decode(string $responseBody): array;
}
