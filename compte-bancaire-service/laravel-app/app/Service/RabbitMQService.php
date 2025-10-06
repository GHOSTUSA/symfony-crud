<?php

namespace App\Service;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use Illuminate\Support\Facades\Log;

class RabbitMQService
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;
    
    private string $host;
    private int $port;
    private string $user;
    private string $password;

    // Queues RabbitMQ
    const QUEUE_ACCOUNT_COMMANDS = 'account.commands';
    const QUEUE_ACCOUNT_EVENTS = 'account.events';
    const QUEUE_SAGA_EVENTS = 'saga.events';

    // Exchanges
    const EXCHANGE_MICROSERVICES = 'microservices';
    const EXCHANGE_SAGA = 'saga';

    public function __construct()
    {
        $this->host = env('RABBITMQ_HOST', 'rabbitmq');
        $this->port = (int) env('RABBITMQ_PORT', 5672);
        $this->user = env('RABBITMQ_USER', 'admin');
        $this->password = env('RABBITMQ_PASSWORD', 'admin123');
    }

    /**
     * Établir la connexion à RabbitMQ
     */
    public function connect(): void
    {
        try {
            if (!$this->connection || !$this->connection->isConnected()) {
                $this->connection = new AMQPStreamConnection(
                    $this->host,
                    $this->port,
                    $this->user,
                    $this->password
                );
                $this->channel = $this->connection->channel();
                $this->setupQueuesAndExchanges();
            }
        } catch (\Exception $e) {
            Log::error('Failed to connect to RabbitMQ', [
                'error' => $e->getMessage(),
                'host' => $this->host,
                'port' => $this->port
            ]);
            throw $e;
        }
    }

    /**
     * Configuration des queues et exchanges
     */
    private function setupQueuesAndExchanges(): void
    {
        // Déclarer les exchanges
        $this->channel->exchange_declare(
            self::EXCHANGE_MICROSERVICES,
            'topic',
            false,
            true,
            false
        );

        $this->channel->exchange_declare(
            self::EXCHANGE_SAGA,
            'topic',
            false,
            true,
            false
        );

        // Déclarer les queues
        $this->channel->queue_declare(
            self::QUEUE_ACCOUNT_COMMANDS,
            false,
            true,
            false,
            false
        );

        $this->channel->queue_declare(
            self::QUEUE_ACCOUNT_EVENTS,
            false,
            true,
            false,
            false
        );

        $this->channel->queue_declare(
            self::QUEUE_SAGA_EVENTS,
            false,
            true,
            false,
            false
        );

        // Bind queues aux exchanges
        $this->channel->queue_bind(
            self::QUEUE_ACCOUNT_COMMANDS,
            self::EXCHANGE_MICROSERVICES,
            'account.command.*'
        );

        $this->channel->queue_bind(
            self::QUEUE_ACCOUNT_EVENTS,
            self::EXCHANGE_MICROSERVICES,
            'account.event.*'
        );

        $this->channel->queue_bind(
            self::QUEUE_SAGA_EVENTS,
            self::EXCHANGE_SAGA,
            'saga.*'
        );
    }

    /**
     * Consommer les commandes de compte
     */
    public function consumeAccountCommands(callable $callback): void
    {
        $this->connect();

        $this->channel->basic_consume(
            self::QUEUE_ACCOUNT_COMMANDS,
            '',
            false,
            false,
            false,
            false,
            function($msg) use ($callback) {
                try {
                    $data = json_decode($msg->body, true);
                    $routingKey = $msg->delivery_info['routing_key'];
                    
                    Log::info('Account command received', [
                        'routing_key' => $routingKey,
                        'data' => $data
                    ]);

                    $result = $callback($data, $routingKey);
                    
                    // Acknowledge le message si le traitement a réussi
                    if ($result === true) {
                        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                    } else {
                        // Reject et requeue en cas d'échec
                        $msg->delivery_info['channel']->basic_nack(
                            $msg->delivery_info['delivery_tag'],
                            false,
                            true
                        );
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to process account command', [
                        'error' => $e->getMessage(),
                        'message' => $msg->body
                    ]);
                    
                    // Reject le message (il sera peut-être retraité)
                    $msg->delivery_info['channel']->basic_nack(
                        $msg->delivery_info['delivery_tag'],
                        false,
                        true
                    );
                }
            }
        );

        // Démarrer la consommation
        while ($this->channel && $this->channel->getConnection()->isConnected()) {
            $this->channel->wait(null, false, 1);
        }
    }

    /**
     * Publier un événement de succès de création de compte
     */
    public function publishAccountCreatedEvent(array $data): void
    {
        $this->connect();

        $message = new AMQPMessage(
            json_encode($data),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'timestamp' => time(),
                'correlation_id' => $data['saga_id'] ?? uniqid()
            ]
        );

        $this->channel->basic_publish(
            $message,
            self::EXCHANGE_SAGA,
            'saga.account_created'
        );

        Log::info('Account created event published', [
            'saga_id' => $data['saga_id'] ?? 'unknown',
            'account_id' => $data['account_data']['id'] ?? 'unknown'
        ]);
    }

    /**
     * Publier un événement d'échec de création de compte
     */
    public function publishAccountCreationFailedEvent(array $data): void
    {
        $this->connect();

        $message = new AMQPMessage(
            json_encode($data),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'timestamp' => time(),
                'correlation_id' => $data['saga_id'] ?? uniqid()
            ]
        );

        $this->channel->basic_publish(
            $message,
            self::EXCHANGE_SAGA,
            'saga.account_creation_failed'
        );

        Log::info('Account creation failed event published', [
            'saga_id' => $data['saga_id'] ?? 'unknown',
            'error' => $data['error'] ?? 'unknown'
        ]);
    }

    /**
     * Publier un événement de suppression de compte
     */
    public function publishAccountDeletedEvent(array $data): void
    {
        $this->connect();

        $message = new AMQPMessage(
            json_encode($data),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'timestamp' => time(),
                'correlation_id' => $data['saga_id'] ?? uniqid()
            ]
        );

        $this->channel->basic_publish(
            $message,
            self::EXCHANGE_SAGA,
            'saga.account_deleted'
        );

        Log::info('Account deleted event published', [
            'saga_id' => $data['saga_id'] ?? 'unknown',
            'user_id' => $data['user_id'] ?? 'unknown'
        ]);
    }

    /**
     * Fermer la connexion
     */
    public function close(): void
    {
        if ($this->channel) {
            $this->channel->close();
        }
        if ($this->connection) {
            $this->connection->close();
        }
    }

    /**
     * Destructor pour s'assurer que les connexions sont fermées
     */
    public function __destruct()
    {
        $this->close();
    }
}