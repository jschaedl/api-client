<?php

declare(strict_types=1);

namespace Api\Request;

trait HeadersAwareRequestTrait
{
    /** @var Header[] */
    private array $headers = [];

    public function headers(): array
    {
        return $this->headers;
    }

    public function addHeader(string $name, string $value): self
    {
        $this->headers[] = new Header($name, $value);

        return $this;
    }
}
