# Laravel CRUD API Project

Un projet d'API REST simple développé avec Laravel et conteneurisé avec Docker, permettant d'effectuer des opérations CRUD (Create, Read, Update, Delete) sur des utilisateurs.

## Architecture du Projet

Le projet suit une architecture en couches avec :
- Repository Pattern pour l'accès aux données
- Service Layer pour la logique métier
- Controllers pour gérer les requêtes HTTP
- Interface pour assurer le découplage des composants

## Technologies Utilisées

- Laravel 10
- PHP 8.4
- Docker & Docker Compose
- Nginx comme serveur web
- SQLite comme base de données

## Configuration Docker

Le projet utilise trois services Docker :
1. **PHP** : Contient l'application Laravel
2. **Nginx** : Serveur web qui gère les requêtes HTTP
3. **Base de données** : SQLite (intégrée)

## Installation

1. Cloner le repository :
```bash
git clone https://github.com/GHOSTUSA/symfony-crud.git
cd symfony-crud
```

2. Lancer les containers Docker :
```bash
docker-compose up -d
```

3. Installer les dépendances :
```bash
docker-compose exec php composer install
```

4. Configurer l'environnement :
- Copier .env.example vers .env
- Générer la clé d'application :
```bash
docker-compose exec php php artisan key:generate
```

5. Lancer les migrations :
```bash
docker-compose exec php php artisan migrate
```

## Endpoints API

L'API expose les endpoints suivants pour la gestion des utilisateurs :

- `GET /api/users` : Récupérer tous les utilisateurs
- `GET /api/users/{id}` : Récupérer un utilisateur spécifique
- `POST /api/users` : Créer un nouvel utilisateur
- `PUT /api/users/{id}` : Mettre à jour un utilisateur
- `DELETE /api/users/{id}` : Supprimer un utilisateur

## Structure du Code

- `app/Http/Controllers` : Contrôleurs de l'API
- `app/Models` : Modèles de données
- `app/Repository` : Implémentation du Repository Pattern
- `app/Service` : Couche de service pour la logique métier
- `app/Interface` : Interfaces pour le Repository Pattern