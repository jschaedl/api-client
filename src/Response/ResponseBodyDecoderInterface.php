<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Response;

interface ResponseBodyDecoderInterface
{
    /**
     * @throws \Throwable when the body cannot be decoded; Response turns it into a DecodingException
     */
    public function decode(string $responseBody): mixed;
}
