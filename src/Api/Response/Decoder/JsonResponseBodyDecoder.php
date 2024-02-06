<?php

declare(strict_types=1);

namespace Api\Response\Decoder;

use Api\Response\ResponseBodyDecoderInterface;

final class JsonResponseBodyDecoder implements ResponseBodyDecoderInterface
{
    /**
     * @return mixed[]
     */
    public function decode(string $responseBody): array
    {
        // todo: handle json errors and types
        return (array) json_decode($responseBody, associative: true, flags: JSON_THROW_ON_ERROR);
    }
}
