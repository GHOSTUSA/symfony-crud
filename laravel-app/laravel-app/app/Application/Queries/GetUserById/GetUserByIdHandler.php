<?php

namespace App\Application\Queries\GetUserById;

use App\Interface\UserRepositoryInterface;
use App\Application\Common\Mediator\IQueryHandler;
use App\Application\Common\Mediator\IQuery;

class GetUserByIdHandler implements IQueryHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function handle(IQuery $query): mixed
    {
        if (!$query instanceof GetUserByIdQuery) {
            throw new \InvalidArgumentException('Handler expects GetUserByIdQuery');
        }

        return $this->userRepository->findById($query->id);
    }
}