<?php

declare(strict_types = 1);

namespace Eufaturo\IdempotencyMiddleware\Tests;

use Eufaturo\IdempotencyMiddleware\IdempotencyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * @param mixed $app
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            IdempotencyServiceProvider::class,
        ];
    }
}
