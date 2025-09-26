# Laravel CRUD API Project

Structure du Projet

```
app/
├── Domain/
│   ├── Entities/
│   │   └── User.php
│   ├── Repositories/
│   │   └── UserRepositoryInterface.php
│   └── ValueObjects/
├── Application/
│   ├── DTOs/
│   │   └── UserDTO.php
│   └── UseCases/
│       ├── CreateUserUseCase.php
│       ├── UpdateUserUseCase.php
│       ├── DeleteUserUseCase.php
│       └── ListUsersUseCase.php
├── Infrastructure/
│   ├── Persistence/
│   │   ├── Eloquent/
│   │   │   ├── Models/
│   │   │   │   └── User.php
│   │   │   └── UserRepository.php
│   │   └── External/
│   └── Providers/
│       └── RepositoryServiceProvider.php
└── Presentation/
    └── Http/
        └── Controllers/
            └── UserController.php
```

## Technologies Utilisées

- Laravel 10
- PHP 8.4
- Docker & Docker Compose
- Architecture Hexagonale
- Nginx comme serveur web
- MySQL comme base de données

## Configuration Docker

Le projet utilise trois services Docker :
1. **PHP** : Contient l'application Laravel avec architecture hexagonale
2. **Nginx** : Serveur web qui gère les requêtes HTTP
3. **MySQL** : Base de données relationnelle

## Principes SOLID appliqués

- **Single Responsibility** : Chaque classe a une seule responsabilité
- **Open/Closed** : L'architecture permet l'extension sans modification
- **Liskov Substitution** : Les interfaces garantissent la substitution
- **Interface Segregation** : Les interfaces sont spécifiques et cohérentes
- **Dependency Inversion** : Le Domain ne dépend d'aucune couche externe

## Installationéveloppé avec Laravel suivant une architecture hexagonale (ports & adapters), conteneurisé avec Docker. Ce projet permet d'effectuer des opérations CRUD (Create, Read, Update, Delete) sur des utilisateurs.

## Architecture Hexagonale

Le projet est structuré en 5 couches distinctes suivant les principes de l'architecture hexagonale :

### 1. Domain Layer (Cœur)
- Entités métier pures
- Règles métier (ex: assignation des rôles utilisateur)
- Interfaces des repositories (ports)
- Aucune dépendance externe

### 2. Application Layer
- Use Cases (cas d'utilisation)
- DTOs (Data Transfer Objects)
- Orchestration des opérations métier
- Dépend uniquement du Domain

### 3. Infrastructure Layer
- Implémentation des repositories (adapters)
- Modèles Eloquent
- Persistence des données
- Communication avec la base de données

### 4. Presentation Layer
- Contrôleurs API
- Gestion des requêtes/réponses HTTP
- Validation des entrées
- Transformation des données

### 5. External Layer
- Intégrations avec services externes
- Adaptateurs pour APIs tierces

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