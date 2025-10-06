<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Service\SagaOrchestrator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected SagaOrchestrator $sagaOrchestrator;

    public function __construct(SagaOrchestrator $sagaOrchestrator)
    {
        $this->sagaOrchestrator = $sagaOrchestrator;
    }

    public function index(): JsonResponse
    {
        try {
            $users = User::all();
            return response()->json([
                'success' => true,
                'data' => $users,
                'total' => $users->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch users', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users'
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'first_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->sagaOrchestrator->orchestrateUserCreation($request->all());

            return response()->json([
                'success' => true,
                'message' => 'User creation initiated',
                'data' => [
                    'saga_id' => $result['saga_id'],
                    'user' => $result['user'],
                    'status' => $result['status']
                ]
            ], 202);

        } catch (\Exception $e) {
            Log::error('Failed to create user via saga', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate user creation: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:8',
            'first_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::findOrFail($id);
            
            $updateData = $request->only(['name', 'email', 'first_name', 'phone']);
            if ($request->has('password')) {
                $updateData['password'] = bcrypt($request->password);
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update user', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user'
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $result = $this->sagaOrchestrator->orchestrateUserDeletion((int)$id);

            return response()->json([
                'success' => true,
                'message' => 'User deletion initiated',
                'data' => [
                    'saga_id' => $result['saga_id'],
                    'status' => $result['status']
                ]
            ], 202);

        } catch (\Exception $e) {
            Log::error('Failed to delete user via saga', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate user deletion: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSagaStatus(string $sagaId): JsonResponse
    {
        try {
            $saga = $this->sagaOrchestrator->getSagaStatus($sagaId);
            
            if (!$saga) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saga not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'saga_id' => $saga->saga_id,
                    'status' => $saga->status,
                    'transaction_type' => $saga->transaction_type,
                    'started_at' => $saga->started_at,
                    'completed_at' => $saga->completed_at,
                    'failed_at' => $saga->failed_at,
                    'error_message' => $saga->error_message,
                    'next_step' => $saga->next_step
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get saga status', [
                'saga_id' => $sagaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get saga status'
            ], 500);
        }
    }
}
