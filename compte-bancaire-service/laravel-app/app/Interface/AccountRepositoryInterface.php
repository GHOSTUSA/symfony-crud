<?php

namespace App\Interface;

use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;

interface AccountRepositoryInterface
{
    public function getAllAccounts(): Collection;

    public function getAccountById(int $id): ?Account;

    public function getAccountByUserId(int $userId): ?Account;

    public function createAccount(array $data): Account;

    public function updateAccount(int $id, array $data): ?Account;

    public function deleteAccount(int $id): bool;

    public function getAccountByNumber(string $accountNumber): ?Account;
}