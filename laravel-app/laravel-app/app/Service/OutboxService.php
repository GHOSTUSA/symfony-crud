<?php

namespace App\Service;

use App\Models\OutboxEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OutboxService
{
    /**
     * Créer un nouvel événement dans l'Outbox
     */
    public function createEvent(array $eventData): OutboxEvent
    {
        return OutboxEvent::create([
            'event_id' => \Illuminate\Support\Str::uuid()->toString(),
            'saga_id' => $eventData['saga_id'],
            'event_type' => $eventData['event_type'],
            'target_service' => $eventData['target_service'],
            'payload' => $eventData['payload'],
            'status' => OutboxEvent::STATUS_PENDING,
            'created_at' => now(),
            'retry_count' => 0
        ]);
    }

    /**
     * Traiter tous les événements en attente
     */
    public function processEvents(): void
    {
        $pendingEvents = OutboxEvent::where('status', OutboxEvent::STATUS_PENDING)
            ->orWhere('status', OutboxEvent::STATUS_RETRY)
            ->orderBy('created_at')
            ->get();

        foreach ($pendingEvents as $event) {
            $this->processEvent($event);
        }
    }

    /**
     * Traiter un événement spécifique
     */
    public function processEvent(OutboxEvent $event): void
    {
        try {
            // Marquer comme en cours de traitement
            $event->update([
                'status' => OutboxEvent::STATUS_PROCESSING,
                'last_attempt_at' => now()
            ]);

            $success = false;

            switch ($event->event_type) {
                case OutboxEvent::EVENT_CREATE_ACCOUNT:
                    $success = $this->sendCreateAccountRequest($event);
                    break;
                
                case OutboxEvent::EVENT_DELETE_ACCOUNT:
                    $success = $this->sendDeleteAccountRequest($event);
                    break;
                
                default:
                    Log::warning('Unknown event type', ['event_type' => $event->event_type]);
                    $event->update(['status' => OutboxEvent::STATUS_FAILED]);
                    return;
            }

            if ($success) {
                $event->update([
                    'status' => OutboxEvent::STATUS_SENT,
                    'sent_at' => now()
                ]);
                Log::info('Event processed successfully', ['event_id' => $event->event_id]);
            } else {
                $this->handleEventFailure($event);
            }

        } catch (\Exception $e) {
            Log::error('Failed to process event', [
                'event_id' => $event->event_id,
                'error' => $e->getMessage()
            ]);
            $this->handleEventFailure($event);
        }
    }

    /**
     * Envoyer une requête de création de compte
     */
    private function sendCreateAccountRequest(OutboxEvent $event): bool
    {
        try {
            $response = Http::timeout(30)->post('http://account-nginx/api/accounts', [
                'user_id' => $event->payload['user_id'],
                'account_type' => $event->payload['account_type'] ?? 'checking',
                'balance' => $event->payload['balance'] ?? 0.00,
                'status' => $event->payload['status'] ?? 'active',
                'saga_id' => $event->saga_id // Importante pour traçabilité
            ]);

            if ($response->successful()) {
                // Récupérer les données du compte créé
                $accountData = $response->json();
                
                // Notifier l'orchestrateur du succès
                $this->notifyOrchestrator($event->saga_id, 'account_created', $accountData);
                
                return true;
            } else {
                Log::error('Account creation failed', [
                    'saga_id' => $event->saga_id,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                // Notifier l'orchestrateur de l'échec
                $this->notifyOrchestrator($event->saga_id, 'account_creation_failed', [
                    'error' => 'HTTP ' . $response->status() . ': ' . $response->body()
                ]);

                return false;
            }

        } catch (\Exception $e) {
            Log::error('Exception during account creation', [
                'saga_id' => $event->saga_id,
                'error' => $e->getMessage()
            ]);

            $this->notifyOrchestrator($event->saga_id, 'account_creation_failed', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envoyer une requête de suppression de compte
     */
    private function sendDeleteAccountRequest(OutboxEvent $event): bool
    {
        try {
            $userId = $event->payload['user_id'];
            
            $response = Http::timeout(30)->delete("http://account-nginx/api/accounts/user/{$userId}", [
                'saga_id' => $event->saga_id
            ]);

            if ($response->successful()) {
                // Notifier l'orchestrateur du succès
                $this->notifyOrchestrator($event->saga_id, 'account_deleted', [
                    'user_id' => $userId
                ]);
                
                return true;
            } else {
                Log::error('Account deletion failed', [
                    'saga_id' => $event->saga_id,
                    'user_id' => $userId,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                $this->notifyOrchestrator($event->saga_id, 'account_deletion_failed', [
                    'error' => 'HTTP ' . $response->status() . ': ' . $response->body()
                ]);

                return false;
            }

        } catch (\Exception $e) {
            Log::error('Exception during account deletion', [
                'saga_id' => $event->saga_id,
                'error' => $e->getMessage()
            ]);

            $this->notifyOrchestrator($event->saga_id, 'account_deletion_failed', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Notifier l'orchestrateur d'un événement
     */
    private function notifyOrchestrator(string $sagaId, string $eventType, array $data): void
    {
        try {
            // Dans une vraie implémentation, ceci pourrait être un message queue
            // Pour notre POC, nous appelons directement l'orchestrateur via le container
            $orchestrator = app()->make(SagaOrchestrator::class);

            switch ($eventType) {
                case 'account_created':
                    $orchestrator->handleAccountCreated($sagaId, $data);
                    break;
                
                case 'account_creation_failed':
                    $orchestrator->handleAccountCreationFailed($sagaId, $data['error']);
                    break;
                
                case 'account_deleted':
                    $orchestrator->handleAccountDeleted($sagaId);
                    break;
                
                default:
                    Log::warning('Unknown orchestrator event type', ['event_type' => $eventType]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to notify orchestrator', [
                'saga_id' => $sagaId,
                'event_type' => $eventType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Gérer l'échec d'un événement
     */
    private function handleEventFailure(OutboxEvent $event): void
    {
        $event->retry_count++;

        if ($event->retry_count >= 3) {
            $event->update([
                'status' => OutboxEvent::STATUS_FAILED,
                'failed_at' => now()
            ]);
            
            Log::error('Event failed after max retries', [
                'event_id' => $event->event_id,
                'retry_count' => $event->retry_count
            ]);
        } else {
            $event->update([
                'status' => OutboxEvent::STATUS_RETRY,
                'retry_count' => $event->retry_count
            ]);
            
            Log::info('Event scheduled for retry', [
                'event_id' => $event->event_id,
                'retry_count' => $event->retry_count
            ]);
        }
    }

    /**
     * Nettoyer les anciens événements traités
     */
    public function cleanupProcessedEvents(int $daysOld = 7): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        return OutboxEvent::where('status', OutboxEvent::STATUS_SENT)
            ->where('sent_at', '<', $cutoffDate)
            ->delete();
    }

    /**
     * Récupérer les statistiques des événements
     */
    public function getEventStats(): array
    {
        return [
            'pending' => OutboxEvent::where('status', OutboxEvent::STATUS_PENDING)->count(),
            'processing' => OutboxEvent::where('status', OutboxEvent::STATUS_PROCESSING)->count(),
            'sent' => OutboxEvent::where('status', OutboxEvent::STATUS_SENT)->count(),
            'failed' => OutboxEvent::where('status', OutboxEvent::STATUS_FAILED)->count(),
            'retry' => OutboxEvent::where('status', OutboxEvent::STATUS_RETRY)->count(),
        ];
    }
}