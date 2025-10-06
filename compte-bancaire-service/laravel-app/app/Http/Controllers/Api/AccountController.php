<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Service\AccountService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    protected AccountService $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    /**
     * Display a listing of accounts
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $request->query('user_id');
            
            if ($userId) {
                // Filtrer par utilisateur spécifique
                $accounts = $this->accountService->getAccountsByUserId((int)$userId);
                $message = "Accounts retrieved successfully for user {$userId}";
            } else {
                // Récupérer tous les comptes
                $accounts = $this->accountService->getAllAccounts();
                $message = 'All accounts retrieved successfully';
            }

            return response()->json([
                'success' => true,
                'data' => $accounts,
                'total' => count($accounts),
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve accounts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created account
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|integer',
                'account_type' => 'sometimes|string|in:checking,savings,business',
                'balance' => 'sometimes|numeric|min:0',
                'status' => 'sometimes|string|in:active,inactive,suspended'
            ]);

            $account = $this->accountService->createAccount($validatedData);

            return response()->json([
                'success' => true,
                'data' => $account,
                'message' => 'Account created successfully'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified account
     */
    public function show(int $id): JsonResponse
    {
        try {
            $account = $this->accountService->getAccountById($id);

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $account,
                'message' => 'Account retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified account
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'account_type' => 'sometimes|string|in:checking,savings,business',
                'balance' => 'sometimes|numeric|min:0',
                'status' => 'sometimes|string|in:active,inactive,suspended'
            ]);

            $account = $this->accountService->updateAccount($id, $validatedData);

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $account,
                'message' => 'Account updated successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified account
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->accountService->deleteAccount($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get account by user ID
     */
    public function getByUserId(int $userId): JsonResponse
    {
        try {
            $account = $this->accountService->getAccountByUserId($userId);

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found for this user'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $account,
                'message' => 'Account retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete account by user ID (for microservice communication)
     */
    public function deleteByUserId(int $userId): JsonResponse
    {
        try {
            $deleted = $this->accountService->deleteAccountByUserId($userId);

            return response()->json([
                'success' => true,
                'deleted' => $deleted,
                'message' => $deleted ? 'Account deleted successfully' : 'No account found for this user'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting account: ' . $e->getMessage()
            ], 500);
        }
    }
}