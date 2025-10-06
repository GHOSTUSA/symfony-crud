<?php

namespace App\Service;

use App\Models\SagaTransaction;
use App\Models\OutboxEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SagaOrchestrator
{
    protected OutboxService $outboxService;

    public function __construct(OutboxService $outboxService)
    {
        $this->outboxService = $outboxService;
    }

    /**
     * Orchestrer la création d'un utilisateur avec compte bancaire
     */
    public function orchestrateUserCreation(array $userData): array
    {
        $sagaId = Str::uuid()->toString();
        
        DB::beginTransaction();
        try {
            // Étape 1: Créer la transaction Saga
            $saga = SagaTransaction::create([
                'saga_id' => $sagaId,
                'transaction_type' => SagaTransaction::TYPE_CREATE_USER,
                'status' => SagaTransaction::STATUS_PENDING,
                'user_data' => $userData,
                'started_at' => now(),
                'next_step' => 'create_user'
            ]);

            // Étape 2: Créer l'utilisateur en local (transaction atomique)
            $user = $this->createUserLocally($userData);
            
            // Étape 3: Mettre à jour le statut de la saga
            $saga->update([
                'status' => SagaTransaction::STATUS_USER_CREATED,
                'user_data' => array_merge($userData, ['user_id' => $user->id]),
                'next_step' => 'create_account'
            ]);

            // Étape 4: Créer l'événement Outbox pour créer le compte bancaire
            $this->outboxService->createEvent([
                'saga_id' => $sagaId,
                'event_type' => OutboxEvent::EVENT_CREATE_ACCOUNT,
                'target_service' => 'account-service',
                'payload' => [
                    'user_id' => $user->id,
                    'account_type' => 'checking',
                    'balance' => 0.00,
                    'status' => 'active'
                ]
            ]);

            DB::commit();

            // Traiter l'événement Outbox immédiatement
            $this->outboxService->processEvents();

            return [
                'success' => true,
                'saga_id' => $sagaId,
                'user' => $user,
                'status' => 'initiated'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Saga creation failed', [
                'saga_id' => $sagaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('Failed to initiate user creation saga: ' . $e->getMessage());
        }
    }

    /**
     * Orchestrer la suppression d'un utilisateur avec compte bancaire
     */
    public function orchestrateUserDeletion(int $userId): array
    {
        $sagaId = Str::uuid()->toString();
        
        DB::beginTransaction();
        try {
            // Vérifier que l'utilisateur existe
            $user = User::findOrFail($userId);

            // Créer la transaction Saga
            $saga = SagaTransaction::create([
                'saga_id' => $sagaId,
                'transaction_type' => SagaTransaction::TYPE_DELETE_USER,
                'status' => SagaTransaction::STATUS_PENDING,
                'user_data' => ['user_id' => $userId, 'user' => $user->toArray()],
                'started_at' => now(),
                'next_step' => 'delete_account'
            ]);

            // Créer l'événement Outbox pour supprimer le compte bancaire
            $this->outboxService->createEvent([
                'saga_id' => $sagaId,
                'event_type' => OutboxEvent::EVENT_DELETE_ACCOUNT,
                'target_service' => 'account-service',
                'payload' => [
                    'user_id' => $userId
                ]
            ]);

            DB::commit();

            // Traiter l'événement Outbox immédiatement
            $this->outboxService->processEvents();

            return [
                'success' => true,
                'saga_id' => $sagaId,
                'status' => 'initiated'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Saga deletion failed', [
                'saga_id' => $sagaId,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Failed to initiate user deletion saga: ' . $e->getMessage());
        }
    }

    /**
     * Gérer la réponse de création de compte
     */
    public function handleAccountCreated(string $sagaId, array $accountData): void
    {
        DB::beginTransaction();
        try {
            $saga = SagaTransaction::where('saga_id', $sagaId)->firstOrFail();
            
            $saga->update([
                'status' => SagaTransaction::STATUS_ACCOUNT_CREATED,
                'account_data' => $accountData,
                'next_step' => 'complete'
            ]);

            // Finaliser la saga
            $this->completeSaga($saga);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to handle account creation', [
                'saga_id' => $sagaId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Gérer l'échec de création de compte
     */
    public function handleAccountCreationFailed(string $sagaId, string $error): void
    {
        DB::beginTransaction();
        try {
            $saga = SagaTransaction::where('saga_id', $sagaId)->firstOrFail();
            
            $saga->update([
                'status' => SagaTransaction::STATUS_COMPENSATING,
                'error_message' => $error,
                'next_step' => 'compensate_user'
            ]);

            // Compenser en supprimant l'utilisateur créé
            if ($saga->transaction_type === SagaTransaction::TYPE_CREATE_USER) {
                $this->compensateUserCreation($saga);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to handle account creation failure', [
                'saga_id' => $sagaId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Gérer la réponse de suppression de compte
     */
    public function handleAccountDeleted(string $sagaId): void
    {
        DB::beginTransaction();
        try {
            $saga = SagaTransaction::where('saga_id', $sagaId)->firstOrFail();
            
            $saga->update([
                'status' => SagaTransaction::STATUS_ACCOUNT_CREATED, // Account supprimé
                'next_step' => 'delete_user'
            ]);

            // Maintenant supprimer l'utilisateur
            if ($saga->transaction_type === SagaTransaction::TYPE_DELETE_USER) {
                $userId = $saga->user_data['user_id'];
                $user = User::find($userId);
                if ($user) {
                    $user->delete();
                }
                
                $this->completeSaga($saga);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to handle account deletion', [
                'saga_id' => $sagaId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Créer un utilisateur localement
     */
    private function createUserLocally(array $userData): User
    {
        $userData['role'] = (strpos($userData['email'], '@company.com') !== false) 
            ? 'Administrateur' 
            : 'Utilisateur standard';
        $userData['password'] = bcrypt($userData['password']);

        return User::create($userData);
    }

    /**
     * Compenser la création d'utilisateur en le supprimant
     */
    private function compensateUserCreation(SagaTransaction $saga): void
    {
        try {
            $userId = $saga->user_data['user_id'] ?? null;
            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->delete();
                    Log::info('User compensated (deleted)', ['user_id' => $userId, 'saga_id' => $saga->saga_id]);
                }
            }

            $saga->update([
                'status' => SagaTransaction::STATUS_COMPENSATED,
                'completed_at' => now(),
                'next_step' => null
            ]);

        } catch (\Exception $e) {
            $saga->update([
                'status' => SagaTransaction::STATUS_FAILED,
                'failed_at' => now(),
                'error_message' => 'Compensation failed: ' . $e->getMessage()
            ]);
            
            Log::error('Failed to compensate user creation', [
                'saga_id' => $saga->saga_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Finaliser une saga avec succès
     */
    private function completeSaga(SagaTransaction $saga): void
    {
        $saga->update([
            'status' => SagaTransaction::STATUS_COMPLETED,
            'completed_at' => now(),
            'next_step' => null
        ]);

        Log::info('Saga completed successfully', ['saga_id' => $saga->saga_id]);
    }

    /**
     * Récupérer le statut d'une saga
     */
    public function getSagaStatus(string $sagaId): ?SagaTransaction
    {
        return SagaTransaction::where('saga_id', $sagaId)->first();
    }
}