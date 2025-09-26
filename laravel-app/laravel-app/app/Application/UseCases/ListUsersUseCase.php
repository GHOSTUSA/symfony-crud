<?php

namespace App\Application\UseCases;

use App\Domain\Repositories\UserRepositoryInterface;
use Illuminate\Support\Collection;

class ListUsersUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private \Illuminate\Contracts\Container\Container $container
    ) {}

    public function execute(): Collection
    {
        return $this->userRepository->findAll();
    }
}