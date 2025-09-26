<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserRole;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\UserRepository;
use App\Infrastructure\Persistence\Eloquent\Models\User as UserModel;
use App\Domain\Entities\User;
use App\Application\DTOs\UserDTO;
use Illuminate\Contracts\Container\Container;
use App\Application\Factories\UserFactory;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        parent::register();
        
        // Bind Repository
        $this->app->singleton(UserRepositoryInterface::class, function (Container $app) {
            return new UserRepository($app);
        });

        // Value Objects
        $this->app->bind(Email::class, function (Container $app, array $parameters) {
            return new Email($parameters['email']);
        });

        $this->app->bind(UserRole::class, function (Container $app, array $parameters) {
            return isset($parameters['email']) 
                ? UserRole::fromEmail($parameters['email'])
                : new UserRole($parameters['role']);
        });

        // Entity Factory
        $this->app->singleton(UserFactory::class, function (Container $app) {
            return new UserFactory($app);
        });
        
        // Entity Factory as Named Binding
        $this->app->bind('UserEntityFactory', function (Container $app) {
            $factory = $app->make(UserFactory::class);
            return function (?int $id, string $name, string $firstName, string $email, ?string $phone, ?string $password, ?string $role = null) use ($factory) {
                return $factory->create($id, $name, $firstName, $email, $phone, $password, $role);
            };
        });
    }

    public function boot(): void
    {
        //
    }
}