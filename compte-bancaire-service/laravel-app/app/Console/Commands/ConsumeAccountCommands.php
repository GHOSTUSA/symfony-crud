<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Service\RabbitMQService;
use App\Service\AccountCommandHandler;
use Illuminate\Support\Facades\Log;

class ConsumeAccountCommands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'account:consume-commands';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume account commands from RabbitMQ';

    protected RabbitMQService $rabbitMQService;
    protected AccountCommandHandler $commandHandler;

    public function __construct(RabbitMQService $rabbitMQService, AccountCommandHandler $commandHandler)
    {
        parent::__construct();
        $this->rabbitMQService = $rabbitMQService;
        $this->commandHandler = $commandHandler;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to consume account commands from RabbitMQ...');
        
        try {
            $this->rabbitMQService->consumeAccountCommands(function($data, $routingKey) {
                $this->info("Processing command: {$routingKey}");
                
                switch ($routingKey) {
                    case 'account.command.create':
                        return $this->handleCreateCommand($data);
                        
                    case 'account.command.delete':
                        return $this->handleDeleteCommand($data);
                        
                    default:
                        $this->warn("Unknown routing key: {$routingKey}");
                        return false;
                }
            });
        } catch (\Exception $e) {
            $this->error('Failed to consume commands: ' . $e->getMessage());
            Log::error('Account commands consumer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }

    private function handleCreateCommand(array $data): bool
    {
        try {
            $success = $this->commandHandler->handleCreateAccountCommand($data);
            if ($success) {
                $this->info("Create account command processed successfully for saga: {$data['saga_id']}");
            } else {
                $this->error("Failed to process create account command for saga: {$data['saga_id']}");
            }
            return $success;
        } catch (\Exception $e) {
            $this->error("Exception in create command: {$e->getMessage()}");
            return false;
        }
    }

    private function handleDeleteCommand(array $data): bool
    {
        try {
            $success = $this->commandHandler->handleDeleteAccountCommand($data);
            if ($success) {
                $this->info("Delete account command processed successfully for saga: {$data['saga_id']}");
            } else {
                $this->error("Failed to process delete account command for saga: {$data['saga_id']}");
            }
            return $success;
        } catch (\Exception $e) {
            $this->error("Exception in delete command: {$e->getMessage()}");
            return false;
        }
    }
}
