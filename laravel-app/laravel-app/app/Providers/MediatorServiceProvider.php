<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Application\Common\Mediator\IMediator;
use App\Application\Common\Mediator\Mediator;
use App\Application\Commands\CreateUser\CreateUserCommand;
use App\Application\Commands\CreateUser\CreateUserHandler;
use App\Application\Commands\UpdateUser\UpdateUserCommand;
use App\Application\Commands\UpdateUser\UpdateUserHandler;
use App\Application\Commands\DeleteUser\DeleteUserCommand;
use App\Application\Commands\DeleteUser\DeleteUserHandler;
use App\Application\Queries\GetAllUsers\GetAllUsersQuery;
use App\Application\Queries\GetAllUsers\GetAllUsersHandler;
use App\Application\Queries\GetUserById\GetUserByIdQuery;
use App\Application\Queries\GetUserById\GetUserByIdHandler;

class MediatorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(IMediator::class, function ($app) {
            $mediator = new Mediator($app);

            // Register Command Handlers
            $mediator->registerCommandHandler(CreateUserCommand::class, CreateUserHandler::class);
            $mediator->registerCommandHandler(UpdateUserCommand::class, UpdateUserHandler::class);
            $mediator->registerCommandHandler(DeleteUserCommand::class, DeleteUserHandler::class);

            // Register Query Handlers
            $mediator->registerQueryHandler(GetAllUsersQuery::class, GetAllUsersHandler::class);
            $mediator->registerQueryHandler(GetUserByIdQuery::class, GetUserByIdHandler::class);

            return $mediator;
        });
    }
}