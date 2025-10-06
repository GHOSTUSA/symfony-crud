<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Service\RabbitMQService;
use App\Service\AccountCommandHandler;
use App\Repository\AccountRepository;

class RabbitMQServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(RabbitMQService::class, function ($app) {
            return new RabbitMQService();
        });

        $this->app->singleton(AccountRepository::class, function ($app) {
            return new AccountRepository();
        });

        $this->app->singleton(AccountCommandHandler::class, function ($app) {
            return new AccountCommandHandler(
                $app->make(AccountRepository::class),
                $app->make(RabbitMQService::class)
            );
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