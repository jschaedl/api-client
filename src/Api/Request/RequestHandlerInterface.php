<?php

declare(strict_types=1);

namespace Api\Request;

use Psr\Http\Message\RequestInterface as Psr7Request;

interface RequestHandlerInterface
{
    public function handle(Psr7Request $psr7Request): Psr7Request;
}
