<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Retry;

use Jschaedl\ApiClient\Exception\ResponseException;
use Jschaedl\ApiClient\Exception\TransportException;
use Jschaedl\ApiClient\Request\RequestInterface;

interface RetryStrategyInterface
{
    /**
     * @param int $retryCount 1 for the first retry, 2 for the second, ...
     *
     * @return float|null seconds to wait before sending the request again, null to give up
     */
    public function getDelay(RequestInterface $request, ResponseException|TransportException $failure, int $retryCount): ?float;
}
