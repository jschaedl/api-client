<?php

declare(strict_types=1);

namespace Api\Request;

final class Header
{
    private const UPPER = '_ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const LOWER = '-abcdefghijklmnopqrstuvwxyz';

    public function __construct(
        private string $name,
        private readonly string $value,
    ) {
        $this->name = strtr($name, self::UPPER, self::LOWER);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): string
    {
        return $this->value;
    }
}
