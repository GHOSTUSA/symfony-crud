<?php

namespace App\Application\UseCases;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Application\DTOs\UserDTO;
use App\Domain\Entities\User;
use Illuminate\Support\Facades\Hash;

class UpdateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function execute(int $userId, UserDTO $userDTO): User
    {
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        if ($userDTO->email !== $user->getEmail()) {
            $user->updateEmail($userDTO->email);
        }

        if ($userDTO->password) {
            $user->updatePassword(Hash::make($userDTO->password));
        }

        return $this->userRepository->update($user);
    }
}