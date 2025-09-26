<?php

namespace App\Interface;

use App\Domain\Entities\User as UserEntity;

interface UserRepositoryInterface 
{
    /** @return UserEntity[] */
    public function all(): array;

    public function find($id): UserEntity;

    public function create(array $data): UserEntity;

    public function update(UserEntity $user, array $data): UserEntity;
    
    public function delete(UserEntity $user): void;
}