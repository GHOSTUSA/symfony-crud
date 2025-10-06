<?php

use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorMiddleware;
use DI\ContainerBuilder;
use App\Controller\UserAggregateController;
use App\Service\MicroserviceAggregator;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

require __DIR__ . '/../vendor/autoload.php';

// Configuration du container DI
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    LoggerInterface::class => function () {
        $logger = new Logger('api-gateway');
        $logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));
        return $logger;
    },
    
    MicroserviceAggregator::class => function (LoggerInterface $logger) {
        return new MicroserviceAggregator($logger);
    },
    
    UserAggregateController::class => function (MicroserviceAggregator $aggregator, LoggerInterface $logger) {
        return new UserAggregateController($aggregator, $logger);
    }
]);

$container = $containerBuilder->build();
AppFactory::setContainer($container);

// Créer l'application Slim
$app = AppFactory::create();

// Middleware de gestion d'erreurs
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Middleware CORS pour les appels cross-origin
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Routes de l'API Gateway

// Health check
$app->get('/api/health', [UserAggregateController::class, 'healthCheck']);

// Route principale : utilisateur avec comptes complets
$app->get('/api/users/{id:[0-9]+}/complete', [UserAggregateController::class, 'getUserComplete']);

// Tous les utilisateurs avec leurs comptes
$app->get('/api/users/complete', [UserAggregateController::class, 'getAllUsersComplete']);

// Résumé utilisateur (nom, prénom, comptes)
$app->get('/api/users/{id:[0-9]+}/summary', [UserAggregateController::class, 'getUserSummary']);

// Statistiques globales
$app->get('/api/stats', [UserAggregateController::class, 'getGlobalStats']);

// Route d'accueil avec documentation
$app->get('/', function ($request, $response) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>API Gateway - Microservices</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .endpoint { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 5px; }
            .method { color: white; padding: 4px 8px; border-radius: 3px; font-weight: bold; }
            .get { background: #28a745; }
            h1 { color: #333; }
            h2 { color: #666; }
        </style>
    </head>
    <body>
        <h1>🚀 API Gateway - Microservices</h1>
        <p>Bienvenue sur l\'API Gateway pour l\'agrégation des données utilisateurs et comptes bancaires.</p>
        
        <h2>📋 Endpoints disponibles</h2>
        
        <div class="endpoint">
            <span class="method get">GET</span> <strong>/api/health</strong><br>
            Health check de l\'API Gateway et des services
        </div>
        
        <div class="endpoint">
            <span class="method get">GET</span> <strong>/api/users/{id}/complete</strong><br>
            Récupérer un utilisateur avec tous ses comptes bancaires
        </div>
        
        <div class="endpoint">
            <span class="method get">GET</span> <strong>/api/users/{id}/summary</strong><br>
            Résumé d\'un utilisateur (nom, prénom, nombre de comptes, solde total)
        </div>
        
        <div class="endpoint">
            <span class="method get">GET</span> <strong>/api/users/complete</strong><br>
            Tous les utilisateurs avec leurs comptes bancaires
        </div>
        
        <div class="endpoint">
            <span class="method get">GET</span> <strong>/api/stats</strong><br>
            Statistiques globales de la plateforme
        </div>
        
        <h2>🏗️ Architecture</h2>
        <p>Cette API Gateway agrège les données provenant de :</p>
        <ul>
            <li><strong>User Service</strong> : Gestion des utilisateurs</li>
            <li><strong>Account Service</strong> : Gestion des comptes bancaires</li>
        </ul>
        
        <h2>📖 Exemple d\'usage</h2>
        <pre>
# Récupérer un utilisateur complet
curl http://localhost:8080/api/users/1/complete

# Health check
curl http://localhost:8080/api/health
        </pre>
    </body>
    </html>';
    
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

// Gestion des routes 404
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    $result = [
        'success' => false,
        'error' => 'Endpoint not found',
        'available_endpoints' => [
            'GET /api/health',
            'GET /api/users/{id}/complete',
            'GET /api/users/{id}/summary', 
            'GET /api/users/complete',
            'GET /api/stats'
        ]
    ];
    
    $response->getBody()->write(json_encode($result, JSON_PRETTY_PRINT));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(404);
});

$app->run();