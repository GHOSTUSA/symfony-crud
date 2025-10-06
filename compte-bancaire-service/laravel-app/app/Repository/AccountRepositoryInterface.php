<?php

namespace App\Repository;

use App\Models\Account;

interface AccountRepositoryInterface
{
    public function findAll();
    public function findById(int $id): ?Account;
    public function findByUserId(int $userId): ?Account;
    public function create(array $data): Account;
    public function update(Account $account, array $data): Account;
    public function delete(Account $account): bool;
}