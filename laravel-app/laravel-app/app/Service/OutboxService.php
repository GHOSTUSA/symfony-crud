<?php

namespace App\Service;

use App\Models\OutboxEvent;
use App\Service\RabbitMQService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OutboxService
{
    protected RabbitMQService $rabbitMQService;

    public function __construct(RabbitMQService $rabbitMQService)
    {
        $this->rabbitMQService = $rabbitMQService;
    }

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
     * Traiter un événement spécifique avec RabbitMQ
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
                    $success = $this->sendCreateAccountCommand($event);
                    break;
                
                case OutboxEvent::EVENT_DELETE_ACCOUNT:
                    $success = $this->sendDeleteAccountCommand($event);
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
                Log::info('Event processed successfully via RabbitMQ', ['event_id' => $event->event_id]);
            } else {
                $this->handleEventFailure($event);
            }

        } catch (\Exception $e) {
            Log::error('Failed to process event via RabbitMQ', [
                'event_id' => $event->event_id,
                'error' => $e->getMessage()
            ]);
            $this->handleEventFailure($event);
        }
    }

    /**
     * Envoyer une commande de création de compte via RabbitMQ
     */
    private function sendCreateAccountCommand(OutboxEvent $event): bool
    {
        try {
            $commandData = [
                'command_type' => 'create_account',
                'saga_id' => $event->saga_id,
                'user_id' => $event->payload['user_id'],
                'account_type' => $event->payload['account_type'] ?? 'checking',
                'balance' => $event->payload['balance'] ?? 0.00,
                'status' => $event->payload['status'] ?? 'active',
                'timestamp' => now()->toISOString()
            ];

            $this->rabbitMQService->publishCreateAccountCommand($commandData);
            
            Log::info('Create account command sent via RabbitMQ', [
                'saga_id' => $event->saga_id,
                'user_id' => $event->payload['user_id']
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send create account command via RabbitMQ', [
                'saga_id' => $event->saga_id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envoyer une commande de suppression de compte via RabbitMQ
     */
    private function sendDeleteAccountCommand(OutboxEvent $event): bool
    {
        try {
            $commandData = [
                'command_type' => 'delete_account',
                'saga_id' => $event->saga_id,
                'user_id' => $event->payload['user_id'],
                'timestamp' => now()->toISOString()
            ];

            $this->rabbitMQService->publishDeleteAccountCommand($commandData);
            
            Log::info('Delete account command sent via RabbitMQ', [
                'saga_id' => $event->saga_id,
                'user_id' => $event->payload['user_id']
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send delete account command via RabbitMQ', [
                'saga_id' => $event->saga_id,
                'error' => $e->getMessage()
            ]);

            return false;
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