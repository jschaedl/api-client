<?php

declare(strict_types=1);

namespace Api\Response\Decoder;

use Api\Response\ResponseBodyDecoderInterface;

final class JsonResponseBodyDecoder implements ResponseBodyDecoderInterface
{
    /**
     * @throws \JsonException
     */
    public function decode(string $responseBody): mixed
    {
        return json_decode($responseBody, associative: true, flags: \JSON_THROW_ON_ERROR);
    }
}
