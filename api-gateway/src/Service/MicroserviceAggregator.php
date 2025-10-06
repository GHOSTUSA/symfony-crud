<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class MicroserviceAggregator
{
    private Client $httpClient;
    private LoggerInterface $logger;
    private array $config;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->httpClient = new Client([
            'timeout' => 10,
            'connect_timeout' => 5,
        ]);
        
        $this->config = [
            'user_service' => $_ENV['USER_SERVICE_URL'] ?? 'http://user-nginx:80',
            'account_service' => $_ENV['ACCOUNT_SERVICE_URL'] ?? 'http://account-nginx:80'
        ];
    }

    /**
     * Récupérer un utilisateur avec ses comptes bancaires
     */
    public function getUserWithAccounts(int $userId): array
    {
        $this->logger->info('Fetching user with accounts', ['user_id' => $userId]);

        try {
            // Requêtes parallèles pour optimiser les performances
            $promises = [
                'user' => $this->getUserAsync($userId),
                'accounts' => $this->getUserAccountsAsync($userId)
            ];

            $responses = $this->resolvePromises($promises, $userId);

            if (!$responses['user']) {
                return [
                    'success' => false,
                    'error' => 'User not found',
                    'code' => 404
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'user' => $responses['user'],
                    'accounts' => $responses['accounts'] ?? [],
                    'total_accounts' => count($responses['accounts'] ?? []),
                    'total_balance' => $this->calculateTotalBalance($responses['accounts'] ?? [])
                ]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to aggregate user data', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Service temporarily unavailable',
                'code' => 503
            ];
        }
    }

    /**
     * Récupérer tous les utilisateurs avec leurs comptes
     */
    public function getAllUsersWithAccounts(): array
    {
        $this->logger->info('Fetching all users with accounts');

        try {
            // Récupérer tous les utilisateurs et tous les comptes
            $promises = [
                'users' => $this->httpClient->getAsync("{$this->config['user_service']}/api/users"),
                'accounts' => $this->httpClient->getAsync("{$this->config['account_service']}/api/accounts")
            ];

            $results = [];
            
            foreach ($promises as $key => $promise) {
                try {
                    $response = $promise->wait();
                    $data = json_decode($response->getBody()->getContents(), true);
                    $results[$key] = $data['success'] ? $data['data'] : [];
                } catch (RequestException $e) {
                    $this->logger->warning("Failed to fetch {$key}", [
                        'error' => $e->getMessage()
                    ]);
                    $results[$key] = [];
                }
            }

            $users = $results['users'] ?? [];
            $allAccounts = $results['accounts'] ?? [];

            if (empty($users)) {
                return [
                    'success' => true,
                    'data' => [],
                    'total' => 0
                ];
            }

            // Associer les comptes aux utilisateurs
            $usersWithAccounts = [];
            foreach ($users as $user) {
                $userAccounts = array_filter($allAccounts, function($account) use ($user) {
                    return $account['user_id'] == $user['id'];
                });
                
                $usersWithAccounts[] = [
                    'user' => $user,
                    'accounts' => array_values($userAccounts),
                    'total_accounts' => count($userAccounts),
                    'total_balance' => $this->calculateTotalBalance($userAccounts)
                ];
            }

            return [
                'success' => true,
                'data' => $usersWithAccounts,
                'total' => count($usersWithAccounts)
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to aggregate all users data', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Service temporarily unavailable',
                'code' => 503
            ];
        }
    }

    /**
     * Requête asynchrone pour récupérer un utilisateur
     */
    private function getUserAsync(int $userId)
    {
        return $this->httpClient->getAsync("{$this->config['user_service']}/api/users/{$userId}");
    }

    /**
     * Requête asynchrone pour récupérer les comptes d'un utilisateur
     */
    private function getUserAccountsAsync(int $userId)
    {
        // Pour le moment, récupérer tous les comptes et filtrer côté gateway
        return $this->httpClient->getAsync("{$this->config['account_service']}/api/accounts");
    }

    /**
     * Résoudre les promesses et extraire les données
     */
    private function resolvePromises(array $promises, int $userId = null): array
    {
        $results = [];
        
        foreach ($promises as $key => $promise) {
            try {
                $response = $promise->wait();
                $data = json_decode($response->getBody()->getContents(), true);
                
                if ($key === 'user') {
                    $results['user'] = $data['success'] ? $data['data'] : null;
                } else {
                    $allAccounts = $data['success'] ? $data['data'] : [];
                    // Filtrer les comptes pour cet utilisateur spécifique
                    if ($userId) {
                        $userAccounts = array_filter($allAccounts, function($account) use ($userId) {
                            return $account['user_id'] == $userId;
                        });
                        $results['accounts'] = array_values($userAccounts);
                    } else {
                        $results['accounts'] = $allAccounts;
                    }
                }
                
            } catch (RequestException $e) {
                $this->logger->warning("Failed to fetch {$key}", [
                    'error' => $e->getMessage(),
                    'status_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : null
                ]);
                
                if ($key === 'user') {
                    $results['user'] = null;
                } else {
                    $results['accounts'] = [];
                }
            }
        }
        
        return $results;
    }

    /**
     * Récupérer un utilisateur (synchrone)
     */
    private function getUser(int $userId): ?array
    {
        try {
            $response = $this->httpClient->get("{$this->config['user_service']}/api/users/{$userId}");
            $data = json_decode($response->getBody()->getContents(), true);
            return $data['success'] ? $data['data'] : null;
        } catch (RequestException $e) {
            $this->logger->warning('Failed to fetch user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Récupérer tous les utilisateurs
     */
    private function getUsers(): ?array
    {
        try {
            $response = $this->httpClient->get("{$this->config['user_service']}/api/users");
            $data = json_decode($response->getBody()->getContents(), true);
            return $data['success'] ? $data['data'] : null;
        } catch (RequestException $e) {
            $this->logger->warning('Failed to fetch users', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Récupérer les comptes d'un utilisateur
     */
    private function getUserAccounts(int $userId): ?array
    {
        try {
            $response = $this->httpClient->get("{$this->config['account_service']}/api/accounts?user_id={$userId}");
            $data = json_decode($response->getBody()->getContents(), true);
            return $data['success'] ? $data['data'] : null;
        } catch (RequestException $e) {
            $this->logger->warning('Failed to fetch user accounts', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Calculer le solde total des comptes
     */
    private function calculateTotalBalance(array $accounts): float
    {
        return array_reduce($accounts, function ($total, $account) {
            return $total + (float)($account['balance'] ?? 0);
        }, 0.0);
    }

    /**
     * Health check des services
     */
    public function healthCheck(): array
    {
        $services = [
            'user_service' => $this->config['user_service'],
            'account_service' => $this->config['account_service']
        ];

        $health = [];
        foreach ($services as $name => $url) {
            try {
                $start = microtime(true);
                $response = $this->httpClient->get("{$url}/api/health", ['timeout' => 3]);
                $responseTime = round((microtime(true) - $start) * 1000, 2);
                
                $health[$name] = [
                    'status' => 'healthy',
                    'response_time_ms' => $responseTime,
                    'url' => $url
                ];
            } catch (\Exception $e) {
                $health[$name] = [
                    'status' => 'unhealthy',
                    'error' => $e->getMessage(),
                    'url' => $url
                ];
            }
        }

        return $health;
    }
}