<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interface\UserRepositoryInterface;
use App\Repository\UserRepository;
use App\Application\Factories\UserFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Repository Interface to Implementation
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // Bind UserFactory
        $this->app->bind(UserFactory::class, function ($app) {
            return new UserFactory($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
