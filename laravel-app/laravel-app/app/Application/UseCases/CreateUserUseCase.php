<?php

namespace App\Application\UseCases;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Application\DTOs\UserDTO;
use App\Domain\Entities\User;
use Illuminate\Contracts\Hashing\Hasher;

class CreateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private \Illuminate\Contracts\Container\Container $container,
        private Hasher $hasher
    ) {}

    public function execute(UserDTO $userDTO): User
    {
        $user = $this->container->make('UserEntityFactory')(
            null,
            $userDTO->name,
            $userDTO->firstName,
            $userDTO->email,
            $userDTO->phone,
            $this->hasher->make($userDTO->password),
            null
        );

        return $this->userRepository->save($user);
    }
}