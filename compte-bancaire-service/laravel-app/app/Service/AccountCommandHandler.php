<?php

namespace App\Service;

use App\Models\Account;
use App\Repository\AccountRepository;
use App\Service\RabbitMQService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AccountCommandHandler
{
    protected AccountRepository $accountRepository;
    protected RabbitMQService $rabbitMQService;

    public function __construct(
        AccountRepository $accountRepository,
        RabbitMQService $rabbitMQService
    ) {
        $this->accountRepository = $accountRepository;
        $this->rabbitMQService = $rabbitMQService;
    }

    /**
     * Traiter une commande de création de compte
     */
    public function handleCreateAccountCommand(array $commandData): bool
    {
        DB::beginTransaction();
        try {
            Log::info('Processing create account command', [
                'saga_id' => $commandData['saga_id'],
                'user_id' => $commandData['user_id']
            ]);

            // Vérifier qu'un compte n'existe pas déjà pour cet utilisateur
            $existingAccount = Account::where('user_id', $commandData['user_id'])->first();
            if ($existingAccount) {
                Log::warning('Account already exists for user', [
                    'saga_id' => $commandData['saga_id'],
                    'user_id' => $commandData['user_id'],
                    'existing_account_id' => $existingAccount->id
                ]);

                // Publier un événement de succès avec le compte existant
                $this->rabbitMQService->publishAccountCreatedEvent([
                    'saga_id' => $commandData['saga_id'],
                    'account_data' => $existingAccount->toArray()
                ]);

                DB::commit();
                return true;
            }

            // Créer le nouveau compte
            $accountData = [
                'user_id' => $commandData['user_id'],
                'account_type' => $commandData['account_type'] ?? 'checking',
                'balance' => $commandData['balance'] ?? 0.00,
                'status' => $commandData['status'] ?? 'active'
            ];

            $account = $this->accountRepository->createAccount($accountData);

            Log::info('Account created successfully', [
                'saga_id' => $commandData['saga_id'],
                'account_id' => $account->id,
                'user_id' => $account->user_id
            ]);

            // Publier l'événement de succès
            $this->rabbitMQService->publishAccountCreatedEvent([
                'saga_id' => $commandData['saga_id'],
                'account_data' => $account->toArray()
            ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create account', [
                'saga_id' => $commandData['saga_id'],
                'user_id' => $commandData['user_id'],
                'error' => $e->getMessage()
            ]);

            // Publier l'événement d'échec
            $this->rabbitMQService->publishAccountCreationFailedEvent([
                'saga_id' => $commandData['saga_id'],
                'error' => $e->getMessage(),
                'user_id' => $commandData['user_id']
            ]);

            return false;
        }
    }

    /**
     * Traiter une commande de suppression de compte
     */
    public function handleDeleteAccountCommand(array $commandData): bool
    {
        DB::beginTransaction();
        try {
            Log::info('Processing delete account command', [
                'saga_id' => $commandData['saga_id'],
                'user_id' => $commandData['user_id']
            ]);

            // Trouver le compte de l'utilisateur
            $account = Account::where('user_id', $commandData['user_id'])->first();
            
            if (!$account) {
                Log::warning('No account found for user', [
                    'saga_id' => $commandData['saga_id'],
                    'user_id' => $commandData['user_id']
                ]);

                // Publier un événement de succès même si pas de compte (idempotence)
                $this->rabbitMQService->publishAccountDeletedEvent([
                    'saga_id' => $commandData['saga_id'],
                    'user_id' => $commandData['user_id']
                ]);

                DB::commit();
                return true;
            }

            // Vérifier si le compte peut être supprimé (solde = 0)
            if ($account->balance != 0) {
                $errorMessage = "Cannot delete account with non-zero balance: {$account->balance}";
                
                Log::error($errorMessage, [
                    'saga_id' => $commandData['saga_id'],
                    'account_id' => $account->id,
                    'balance' => $account->balance
                ]);

                $this->rabbitMQService->publishAccountCreationFailedEvent([
                    'saga_id' => $commandData['saga_id'],
                    'error' => $errorMessage,
                    'user_id' => $commandData['user_id']
                ]);

                DB::rollBack();
                return false;
            }

            // Supprimer le compte
            $this->accountRepository->deleteAccount($account->id);

            Log::info('Account deleted successfully', [
                'saga_id' => $commandData['saga_id'],
                'account_id' => $account->id,
                'user_id' => $account->user_id
            ]);

            // Publier l'événement de succès
            $this->rabbitMQService->publishAccountDeletedEvent([
                'saga_id' => $commandData['saga_id'],
                'user_id' => $commandData['user_id'],
                'deleted_account_id' => $account->id
            ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete account', [
                'saga_id' => $commandData['saga_id'],
                'user_id' => $commandData['user_id'],
                'error' => $e->getMessage()
            ]);

            // En cas d'erreur de suppression, on considère cela comme un échec critique
            // qui nécessite une intervention manuelle
            return false;
        }
    }
}