<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Request;

/**
 * A request that brings its own body encoder, overriding the one the client was built with
 * (e.g. a form-encoded OAuth token request on an otherwise JSON API).
 */
interface BodyEncoderAwareRequestInterface extends RequestInterface
{
    public function bodyEncoder(): ?RequestBodyEncoderInterface;
}
