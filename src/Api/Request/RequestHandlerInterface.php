<?php

declare(strict_types=1);

namespace Api\Request;

interface RequestHandlerInterface
{
    public function handle(RequestInterface $request): RequestInterface;
}
