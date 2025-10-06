<?php

namespace App\Service;

use App\Interface\AccountRepositoryInterface;
use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\Factory as HttpClient;

class AccountService
{
    protected AccountRepositoryInterface $accountRepository;
    protected HttpClient $httpClient;

    public function __construct(AccountRepositoryInterface $accountRepository, HttpClient $httpClient)
    {
        $this->accountRepository = $accountRepository;
        $this->httpClient = $httpClient;
    }

    /**
     * Get all accounts
     */
    public function getAllAccounts(): Collection
    {
        return $this->accountRepository->getAllAccounts();
    }

    /**
     * Get account by ID
     */
    public function getAccountById(int $id): ?Account
    {
        return $this->accountRepository->getAccountById($id);
    }

    /**
     * Get account by user ID
     */
    public function getAccountByUserId(int $userId): ?Account
    {
        return $this->accountRepository->getAccountByUserId($userId);
    }

    /**
     * Create a new account
     */
    public function createAccount(array $data): Account
    {
        // Validate that the user exists by calling the User microservice
        if (isset($data['user_id'])) {
            $userExists = $this->checkUserExists($data['user_id']);
            if (!$userExists) {
                throw new \Exception('User does not exist');
            }
        }

        return $this->accountRepository->createAccount($data);
    }

    /**
     * Update an account
     */
    public function updateAccount(int $id, array $data): ?Account
    {
        return $this->accountRepository->updateAccount($id, $data);
    }

    /**
     * Delete an account
     */
    public function deleteAccount(int $id): bool
    {
        return $this->accountRepository->deleteAccount($id);
    }

    /**
     * Delete account by user ID (for microservice communication)
     */
    public function deleteAccountByUserId(int $userId): bool
    {
        $account = $this->accountRepository->getAccountByUserId($userId);
        if ($account) {
            return $this->accountRepository->deleteAccount($account->id);
        }
        return false;
    }

    /**
     * Check if user exists by calling User microservice
     */
    private function checkUserExists(int $userId): bool
    {
        try {
            $response = $this->httpClient->get("http://user-nginx/api/users/{$userId}");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get account by account number
     */
    public function getAccountByNumber(string $accountNumber): ?Account
    {
        return $this->accountRepository->getAccountByNumber($accountNumber);
    }
}