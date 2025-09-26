<?php

namespace App\Application\Factories;

use App\Domain\Entities\User;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserRole;
use Illuminate\Contracts\Container\Container;

class UserFactory
{
    public function __construct(
        private Container $container
    ) {}

    public function create(
        ?int $id,
        string $name,
        string $firstName,
        string $email,
        ?string $phone,
        ?string $password,
        ?string $role = null
    ): User {
        return new User(
            $id,
            $name,
            $firstName,
            $this->container->make(Email::class, ['email' => $email]),
            $phone,
            $password,
            $role === null 
                ? $this->container->make(UserRole::class, ['email' => $email])
                : $this->container->make(UserRole::class, ['role' => $role])
        );
    }
}