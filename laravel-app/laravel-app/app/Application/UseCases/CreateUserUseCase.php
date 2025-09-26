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
            $userDTO->name,
            $userDTO->firstName,
            $userDTO->email,
            $userDTO->phone,
            Hash::make($userDTO->password)
        );

        return $this->userRepository->save($user);
    }
}