<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\User;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    public function findAll(): Collection;
    public function findById(int $id): ?User;
    public function save(User $user): User;
    public function update(User $user): User;
    public function delete(User $user): void;
}