<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Request;

interface RequestHandlerInterface
{
    public function handle(RequestInterface $request): RequestInterface;
}
