<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Request\Encoder;

use Jschaedl\ApiClient\Request\RequestBodyEncoderInterface;

/**
 * application/x-www-form-urlencoded, e.g. for OAuth token endpoints.
 */
final class FormRequestBodyEncoder implements RequestBodyEncoderInterface
{
    public function encode(array $requestBody): string
    {
        return http_build_query($requestBody, '', '&', \PHP_QUERY_RFC1738);
    }

    public function contentType(): string
    {
        return 'application/x-www-form-urlencoded';
    }
}
