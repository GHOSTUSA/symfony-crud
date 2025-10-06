<?php

namespace App\Service;

use App\Interface\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Log;

class UserService
{
    protected $userRepo;
    protected $httpClient;

    public function __construct(UserRepositoryInterface $userRepo, HttpClient $httpClient)
    {
        $this->userRepo = $userRepo;
        $this->httpClient = $httpClient;
    }

    public function listUsers()
    {
        return $this->userRepo->all();
    }

    public function createUser(array $data)
    {
        $data['role'] = (strpos($data['email'], '@company.com') !== false) ? 'Administrateur' : 'Utilisateur standard';
        $data['password'] = Hash::make($data['password']);

        try {
            // Create user first
            $user = $this->userRepo->create($data);

            // Then create associated bank account
            $this->createBankAccount($user->id);

            return $user;
        } catch (\Exception $e) {
            // If account creation fails, rollback user creation
            if (isset($user)) {
                $this->userRepo->delete($user);
            }
            throw new \Exception('Failed to create user and associated bank account: ' . $e->getMessage());
        }
    }

    public function getUser($id)
    {
        return $this->userRepo->find($id);
    }

    public function updateUser(User $user, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (isset($data['email'])) {
            $data['role'] = (strpos($data['email'], '@company.com') !== false) ? 'Administrateur' : 'Utilisateur standard';
        }

        return $this->userRepo->update($user, $data);
    }

    public function deleteUser(User $user)
    {
        try {
            // First, delete associated bank account
            $this->deleteBankAccount($user->id);

            // Then delete the user
            $this->userRepo->delete($user);
        } catch (\Exception $e) {
            Log::warning('Failed to delete bank account for user ' . $user->id . ': ' . $e->getMessage());
            // Continue with user deletion even if bank account deletion fails
            $this->userRepo->delete($user);
        }
    }

    /**
     * Create a bank account for the user via Account microservice
     */
    private function createBankAccount(int $userId): void
    {
        try {
            $response = $this->httpClient->post('http://account-nginx/api/accounts', [
                'user_id' => $userId,
                'account_type' => 'checking',
                'balance' => 0.00,
                'status' => 'active'
            ]);

            if (!$response->successful()) {
                throw new \Exception('Account service responded with error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Failed to create bank account for user ' . $userId . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a bank account for the user via Account microservice
     */
    private function deleteBankAccount(int $userId): void
    {
        try {
            $response = $this->httpClient->delete("http://account-nginx/api/accounts/user/{$userId}");

            if (!$response->successful()) {
                Log::warning('Failed to delete bank account for user ' . $userId . ': ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Error communicating with account service for user ' . $userId . ': ' . $e->getMessage());
            throw $e;
        }
    }
}
