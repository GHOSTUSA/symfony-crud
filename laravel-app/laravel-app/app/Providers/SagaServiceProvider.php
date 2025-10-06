<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Service\SagaOrchestrator;
use App\Service\OutboxService;

class SagaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(OutboxService::class, function ($app) {
            return new OutboxService();
        });

        $this->app->singleton(SagaOrchestrator::class, function ($app) {
            return new SagaOrchestrator($app->make(OutboxService::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}