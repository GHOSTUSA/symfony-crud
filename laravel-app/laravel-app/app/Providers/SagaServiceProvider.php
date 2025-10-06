<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Service\SagaOrchestrator;
use App\Service\OutboxService;
use App\Service\RabbitMQService;

class SagaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(RabbitMQService::class, function ($app) {
            return new RabbitMQService();
        });

        $this->app->singleton(OutboxService::class, function ($app) {
            return new OutboxService($app->make(RabbitMQService::class));
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