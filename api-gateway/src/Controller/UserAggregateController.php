<?php

namespace App\Controller;

use App\Service\MicroserviceAggregator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class UserAggregateController
{
    private MicroserviceAggregator $aggregator;
    private LoggerInterface $logger;

    public function __construct(MicroserviceAggregator $aggregator, LoggerInterface $logger)
    {
        $this->aggregator = $aggregator;
        $this->logger = $logger;
    }

    /**
     * GET /api/users/{id}/complete
     * Récupérer un utilisateur avec ses comptes bancaires
     */
    public function getUserComplete(Request $request, Response $response, array $args): Response
    {
        $userId = (int) $args['id'];
        
        $this->logger->info('API Gateway: Getting complete user data', ['user_id' => $userId]);

        $result = $this->aggregator->getUserWithAccounts($userId);

        $statusCode = $result['success'] ? 200 : ($result['code'] ?? 500);
        
        $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    /**
     * GET /api/users/complete
     * Récupérer tous les utilisateurs avec leurs comptes bancaires
     */
    public function getAllUsersComplete(Request $request, Response $response): Response
    {
        $this->logger->info('API Gateway: Getting all users with accounts');

        $result = $this->aggregator->getAllUsersWithAccounts();

        $statusCode = $result['success'] ? 200 : ($result['code'] ?? 500);
        
        $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    /**
     * GET /api/health
     * Health check de l'API Gateway et des services
     */
    public function healthCheck(Request $request, Response $response): Response
    {
        $this->logger->info('API Gateway: Health check requested');

        try {
            $servicesHealth = $this->aggregator->healthCheck();
            
            $allHealthy = array_reduce($servicesHealth, function ($carry, $service) {
                return $carry && ($service['status'] === 'healthy');
            }, true);

            $result = [
                'api_gateway' => [
                    'status' => 'healthy',
                    'timestamp' => date('c'),
                    'version' => '1.0.0'
                ],
                'services' => $servicesHealth,
                'overall_status' => $allHealthy ? 'healthy' : 'degraded',
                'success' => true
            ];

            // Toujours retourner 200 pour le health check, le statut est dans la réponse
            $statusCode = 200;
            
        } catch (\Exception $e) {
            $this->logger->error('Health check failed', ['error' => $e->getMessage()]);
            
            $result = [
                'api_gateway' => [
                    'status' => 'unhealthy',
                    'timestamp' => date('c'),
                    'version' => '1.0.0',
                    'error' => $e->getMessage()
                ],
                'services' => [],
                'overall_status' => 'unhealthy',
                'success' => false
            ];
            
            $statusCode = 200; // Même en cas d'erreur, retourner 200 avec le statut dans la réponse
        }
        
        $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    /**
     * GET /api/users/{id}/summary
     * Résumé rapide d'un utilisateur (nom, prénom, nombre de comptes, solde total)
     */
    public function getUserSummary(Request $request, Response $response, array $args): Response
    {
        $userId = (int) $args['id'];
        
        $this->logger->info('API Gateway: Getting user summary', ['user_id' => $userId]);

        $result = $this->aggregator->getUserWithAccounts($userId);

        if (!$result['success']) {
            $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($result['code'] ?? 500);
        }

        // Créer un résumé concis
        $userData = $result['data'];
        $summary = [
            'success' => true,
            'data' => [
                'user_id' => $userData['user']['id'],
                'full_name' => trim($userData['user']['first_name'] . ' ' . $userData['user']['name']),
                'email' => $userData['user']['email'],
                'total_accounts' => $userData['total_accounts'],
                'total_balance' => $userData['total_balance'],
                'accounts_summary' => array_map(function ($account) {
                    return [
                        'account_number' => $account['account_number'],
                        'type' => $account['account_type'],
                        'balance' => $account['balance'],
                        'status' => $account['status']
                    ];
                }, $userData['accounts'])
            ]
        ];
        
        $response->getBody()->write(json_encode($summary, JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * GET /api/stats
     * Statistiques globales de la plateforme
     */
    public function getGlobalStats(Request $request, Response $response): Response
    {
        $this->logger->info('API Gateway: Getting global statistics');

        $result = $this->aggregator->getAllUsersWithAccounts();

        if (!$result['success']) {
            $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($result['code'] ?? 500);
        }

        $users = $result['data'];
        
        $stats = [
            'success' => true,
            'data' => [
                'total_users' => count($users),
                'total_accounts' => array_sum(array_column($users, 'total_accounts')),
                'total_platform_balance' => array_sum(array_column($users, 'total_balance')),
                'average_accounts_per_user' => count($users) > 0 
                    ? round(array_sum(array_column($users, 'total_accounts')) / count($users), 2) 
                    : 0,
                'average_balance_per_user' => count($users) > 0 
                    ? round(array_sum(array_column($users, 'total_balance')) / count($users), 2) 
                    : 0,
                'users_with_multiple_accounts' => count(array_filter($users, function ($user) {
                    return $user['total_accounts'] > 1;
                })),
                'timestamp' => date('c')
            ]
        ];
        
        $response->getBody()->write(json_encode($stats, JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}