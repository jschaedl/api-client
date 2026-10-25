<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Request;

enum Method: string
{
    case GET = 'GET';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';

    /**
     * Idempotent per RFC 9110: sending the request twice has the same effect as sending it once.
     */
    public function isIdempotent(): bool
    {
        return match ($this) {
            self::POST, self::PATCH => false,
            default => true,
        };
    }
}
