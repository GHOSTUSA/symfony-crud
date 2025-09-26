<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\UserRepository;
use App\Application\UseCases\CreateUserUseCase;
use App\Application\UseCases\UpdateUserUseCase;
use App\Application\UseCases\DeleteUserUseCase;
use App\Application\UseCases\ListUsersUseCase;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Repository Interface to Implementation
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        // Bind Use Cases
        $this->app->bind(CreateUserUseCase::class, function ($app) {
            return new CreateUserUseCase($app->make(UserRepositoryInterface::class));
        });

        $this->app->bind(UpdateUserUseCase::class, function ($app) {
            return new UpdateUserUseCase($app->make(UserRepositoryInterface::class));
        });

        $this->app->bind(DeleteUserUseCase::class, function ($app) {
            return new DeleteUserUseCase($app->make(UserRepositoryInterface::class));
        });

        $this->app->bind(ListUsersUseCase::class, function ($app) {
            return new ListUsersUseCase($app->make(UserRepositoryInterface::class));
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
