<?php

namespace App\Application\Queries\GetAllUsers;

use App\Interface\UserRepositoryInterface;
use App\Application\Common\Mediator\IQueryHandler;
use App\Application\Common\Mediator\IQuery;

class GetAllUsersHandler implements IQueryHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function handle(IQuery $query): array
    {
        if (!$query instanceof GetAllUsersQuery) {
            throw new \InvalidArgumentException('Handler expects GetAllUsersQuery');
        }
        
        return $this->userRepository->all();
    }
}