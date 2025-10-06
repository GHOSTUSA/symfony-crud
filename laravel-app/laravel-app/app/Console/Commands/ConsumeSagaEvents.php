<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Service\RabbitMQService;
use App\Service\SagaOrchestrator;
use Illuminate\Support\Facades\Log;

class ConsumeSagaEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saga:consume-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume saga events from RabbitMQ';

    protected RabbitMQService $rabbitMQService;
    protected SagaOrchestrator $sagaOrchestrator;

    public function __construct(RabbitMQService $rabbitMQService, SagaOrchestrator $sagaOrchestrator)
    {
        parent::__construct();
        $this->rabbitMQService = $rabbitMQService;
        $this->sagaOrchestrator = $sagaOrchestrator;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to consume saga events from RabbitMQ...');
        
        try {
            $this->rabbitMQService->consumeSagaEvents(function($data, $routingKey) {
                $this->info("Processing event: {$routingKey}");
                
                switch ($routingKey) {
                    case 'saga.account_created':
                        $this->handleAccountCreated($data);
                        break;
                        
                    case 'saga.account_creation_failed':
                        $this->handleAccountCreationFailed($data);
                        break;
                        
                    case 'saga.account_deleted':
                        $this->handleAccountDeleted($data);
                        break;
                        
                    default:
                        $this->warn("Unknown routing key: {$routingKey}");
                }
            });
        } catch (\Exception $e) {
            $this->error('Failed to consume events: ' . $e->getMessage());
            Log::error('Saga events consumer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }

    private function handleAccountCreated(array $data): void
    {
        try {
            $this->sagaOrchestrator->handleAccountCreated(
                $data['saga_id'],
                $data['account_data'] ?? []
            );
            $this->info("Account created event processed for saga: {$data['saga_id']}");
        } catch (\Exception $e) {
            $this->error("Failed to handle account created: {$e->getMessage()}");
        }
    }

    private function handleAccountCreationFailed(array $data): void
    {
        try {
            $this->sagaOrchestrator->handleAccountCreationFailed(
                $data['saga_id'],
                $data['error'] ?? 'Unknown error'
            );
            $this->info("Account creation failed event processed for saga: {$data['saga_id']}");
        } catch (\Exception $e) {
            $this->error("Failed to handle account creation failed: {$e->getMessage()}");
        }
    }

    private function handleAccountDeleted(array $data): void
    {
        try {
            $this->sagaOrchestrator->handleAccountDeleted($data['saga_id']);
            $this->info("Account deleted event processed for saga: {$data['saga_id']}");
        } catch (\Exception $e) {
            $this->error("Failed to handle account deleted: {$e->getMessage()}");
        }
    }
}
