<?php

declare(strict_types=1);

namespace Api;

use Api\Exception\ServerException;
use Api\Response\ResponseInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Api\RetryTrait
 *
 * @uses \Api\Response\Response
 * @uses \Api\Exception\ResponseException
 * @uses \Api\Exception\ServerException
 *
 * @group integration
 */
final class RetryTraitTest extends TestCase
{
    public function test_no_retry(): void
    {
        $testRetry = new class {
            use RetryTrait;

            public function run(): ResponseInterface
            {
                return $this->retry(fn (): ResponseInterface => new SuccessResponse());
            }
        };

        self::assertEquals(200, $testRetry->run()->statusCode());
    }

    public function test_retry_successful_before_max_attempts_reached(): void
    {
        $testRetry = new class {
            use RetryTrait;

            public function run(): ResponseInterface
            {
                $attempt = 0;

                return $this->retry(
                    function () use (&$attempt): ResponseInterface {
                        ++$attempt;
                        if ($attempt <= 2) {
                            throw new ServerException(500, 'attempts reached');
                        }

                        return new SuccessResponse();
                    },
                    3,
                    true
                );
            }

            private function getWaitTimeFactor(): int
            {
                return 1;
            }
        };

        self::assertEquals(200, $testRetry->run()->statusCode());
    }

    public function test_retry_throws_exception_after_maximum_retries(): void
    {
        $testRetry = new class {
            use RetryTrait;

            public function run(): ResponseInterface
            {
                $attempt = 0;

                return $this->retry(
                    function () use (&$attempt): ResponseInterface {
                        ++$attempt;
                        if ($attempt <= 2) {
                            throw new \Exception('max attempts reached');
                        }

                        return new SuccessResponse();
                    },
                    1,
                    true
                );
            }
        };

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('max attempts reached');

        $testRetry->run();
    }

    public function test_retry_re_throws_exception(): void
    {
        $testRetry = new class {
            use RetryTrait;

            public function run(): ResponseInterface
            {
                return $this->retry(fn () => throw new \Exception('failure'));
            }
        };

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('failure');

        $testRetry->run();
    }

    public function test_retry_throws_exception_on_negative_attempts(): void
    {
        $testRetry = new class {
            use RetryTrait;

            public function run(): ResponseInterface
            {
                return $this->retry(
                    fn (): ResponseInterface => new SuccessResponse(),
                    -1,
                    true
                );
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('negative');

        $testRetry->run();
    }
}

final class SuccessResponse implements ResponseInterface
{
    /**
     * @return mixed[]
     */
    public function body(): array
    {
        return [];
    }

    public function headers(): array
    {
        return [];
    }

    public function statusCode(): int
    {
        return 200;
    }
}
