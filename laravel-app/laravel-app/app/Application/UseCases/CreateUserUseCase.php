<?php

namespace App\Application\UseCases;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Application\DTOs\UserDTO;
use App\Domain\Entities\User;
use Illuminate\Support\Facades\Hash;

class CreateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function execute(UserDTO $userDTO): User
    {
        $user = new User(
            null, // id is null for new users
            $userDTO->name,
            $userDTO->firstName,
            new \App\Domain\ValueObjects\Email($userDTO->email),
            $userDTO->phone,
            Hash::make($userDTO->password),
            null // role will be determined from email
        );

        return $this->userRepository->save($user);
    }
}