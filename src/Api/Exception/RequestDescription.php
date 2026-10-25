<?php

declare(strict_types=1);

namespace Api\Exception;

use Api\Request\RequestInterface;

/**
 * Describes a request for exception messages as "METHOD /path".
 *
 * The query string is dropped on purpose: some APIs only accept credentials as query
 * parameters, and exception messages end up in logs and error trackers.
 *
 * @internal
 */
final class RequestDescription
{
    public static function of(RequestInterface $request): string
    {
        $path = explode('?', $request->uri(), 2)[0];

        return sprintf('%s %s', strtoupper($request->method()), '' === $path ? '/' : $path);
    }
}
