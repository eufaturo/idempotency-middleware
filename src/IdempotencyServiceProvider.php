<?php

declare(strict_types = 1);

namespace Eufaturo\IdempotencyMiddleware;

use Illuminate\Support\ServiceProvider;

class IdempotencyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->offerPublishing();
    }

    public function register(): void
    {
        $this->configure();
    }

    private function configure(): void
    {
        $this->mergeConfigFrom(
            path: realpath(__DIR__.'/../config/idempotency.php'),
            key: 'eufaturo.idempotency',
        );
    }

    private function offerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                paths: [
                    realpath(__DIR__.'/../config/idempotency.php') => $this->app->configPath('eufaturo/idempotency.php'),
                ],
                groups: ['config', 'eufaturo', 'eufaturo-idempotency-config'],
            );
        }
    }
}
