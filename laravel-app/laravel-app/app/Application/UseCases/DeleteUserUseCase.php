<?php

namespace App\Application\UseCases;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Entities\User;

class DeleteUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private \Illuminate\Contracts\Container\Container $container
    ) {}

    public function execute(int $userId): void
    {
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $this->userRepository->delete($user);
    }
}