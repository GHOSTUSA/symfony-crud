<?php

namespace App\Interface;

use App\Domain\Entities\User as UserEntity;

interface UserRepositoryInterface 
{
    /** @return UserEntity[] */
    public function all(): array;

    public function findById(int $id): UserEntity;

    public function create(UserEntity $user): UserEntity;

    public function update(UserEntity $user): UserEntity;
    
    public function delete(UserEntity $user): void;
}