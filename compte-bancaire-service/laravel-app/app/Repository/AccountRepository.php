<?php

namespace App\Repository;

use App\Interface\AccountRepositoryInterface;
use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;

class AccountRepository implements AccountRepositoryInterface
{
    /**
     * Get all accounts
     */
    public function getAllAccounts(): Collection
    {
        return Account::all();
    }

    /**
     * Get account by ID
     */
    public function getAccountById(int $id): ?Account
    {
        return Account::find($id);
    }

    /**
     * Get account by user ID
     */
    public function getAccountByUserId(int $userId): ?Account
    {
        return Account::where('user_id', $userId)->first();
    }

    /**
     * Create a new account
     */
    public function createAccount(array $data): Account
    {
        // Generate account number if not provided
        if (!isset($data['account_number'])) {
            $data['account_number'] = Account::generateAccountNumber();
        }

        // Set default balance if not provided
        if (!isset($data['balance'])) {
            $data['balance'] = 0.00;
        }

        // Set default account type if not provided
        if (!isset($data['account_type'])) {
            $data['account_type'] = 'checking';
        }

        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'active';
        }

        return Account::create($data);
    }

    /**
     * Update an account
     */
    public function updateAccount(int $id, array $data): ?Account
    {
        $account = Account::find($id);
        if ($account) {
            $account->update($data);
            return $account->fresh();
        }
        return null;
    }

    /**
     * Delete an account
     */
    public function deleteAccount(int $id): bool
    {
        $account = Account::find($id);
        if ($account) {
            return $account->delete();
        }
        return false;
    }

    /**
     * Get account by account number
     */
    public function getAccountByNumber(string $accountNumber): ?Account
    {
        return Account::where('account_number', $accountNumber)->first();
    }
}