# Architecture Microservices - Gestion des Utilisateurs et Comptes Bancaires

## 🏗️ Architecture

Cette application implémente une architecture microservices avec deux services indépendants :

### 1. Microservice Utilisateur (User Service)
- **Port:** 8082
- **Base de données:** user_service (MySQL sur port 3308)
- **Responsabilités:**
  - CRUD complet des utilisateurs
  - Gestion des profils utilisateur
  - Communication avec le service CompteBancaire

### 2. Microservice CompteBancaire (Account Service)
- **Port:** 8081
- **Base de données:** account_service (MySQL sur port 3307)
- **Responsabilités:**
  - CRUD complet des comptes bancaires
  - Gestion des soldes et types de comptes
  - Validation de l'existence des utilisateurs

## 🔄 Communication Inter-Services

Les microservices communiquent via HTTP REST API :

1. **Création d'utilisateur** → Création automatique d'un compte bancaire
2. **Suppression d'utilisateur** → Suppression du compte bancaire associé
3. **Validation d'utilisateur** → Vérification via l'API User Service

## 🚀 Démarrage

```bash
# Construire et démarrer tous les services
docker-compose up --build

# Démarrer en arrière-plan
docker-compose up -d
```

## 📋 Endpoints API

### User Service (http://localhost:8082/api)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/users` | Liste tous les utilisateurs |
| POST | `/users` | Créer un utilisateur (+ compte bancaire) |
| GET | `/users/{id}` | Récupérer un utilisateur |
| PUT/PATCH | `/users/{id}` | Modifier un utilisateur |
| DELETE | `/users/{id}` | Supprimer un utilisateur (+ compte bancaire) |
| GET | `/health` | Status du service |

### Account Service (http://localhost:8081/api)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/accounts` | Liste tous les comptes |
| POST | `/accounts` | Créer un compte bancaire |
| GET | `/accounts/{id}` | Récupérer un compte |
| PUT/PATCH | `/accounts/{id}` | Modifier un compte |
| DELETE | `/accounts/{id}` | Supprimer un compte |
| GET | `/accounts/user/{userId}` | Récupérer le compte d'un utilisateur |
| DELETE | `/accounts/user/{userId}` | Supprimer le compte d'un utilisateur |
| GET | `/health` | Status du service |

## 🏛️ Structure des Données

### Utilisateur
```json
{
  "id": 1,
  "name": "John Doe",
  "first_name": "John",
  "email": "john@example.com",
  "phone": "+33123456789",
  "role": "Utilisateur standard",
  "created_at": "2025-10-06T10:00:00Z",
  "updated_at": "2025-10-06T10:00:00Z"
}
```

### Compte Bancaire
```json
{
  "id": 1,
  "user_id": 1,
  "account_number": "ACC123456789",
  "balance": 0.00,
  "account_type": "checking",
  "status": "active",
  "created_at": "2025-10-06T10:00:00Z",
  "updated_at": "2025-10-06T10:00:00Z"
}
```

## 🔒 Transactions Atomiques

L'architecture garantit la cohérence des données :

1. **Création utilisateur** : Si la création du compte bancaire échoue, l'utilisateur est supprimé
2. **Suppression utilisateur** : Le compte bancaire est supprimé en premier, puis l'utilisateur

## 🗄️ Bases de Données

Chaque microservice possède sa propre base de données :

- **user_service** : Table `users` avec les données utilisateur
- **account_service** : Table `accounts` avec les données des comptes bancaires

## 🐳 Services Docker

| Service | Container | Port | Description |
|---------|-----------|------|-------------|
| user-service | user_service | - | Application Laravel (User) |
| user-nginx | user_nginx | 8080 | Serveur web (User) |
| user-db | user_db | 3306 | Base de données MySQL (User) |
| account-service | account_service | - | Application Laravel (Account) |
| account-nginx | account_nginx | 8081 | Serveur web (Account) |
| account-db | account_db | 3307 | Base de données MySQL (Account) |

## 🔍 Tests

### Créer un utilisateur avec compte bancaire automatique
```bash
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "first_name": "John",
    "email": "john@example.com",
    "phone": "+33123456789",
    "password": "password123"
  }'
```

### Vérifier le compte bancaire créé
```bash
curl http://localhost:8081/api/accounts/user/1
```

### Supprimer l'utilisateur (supprime aussi le compte)
```bash
curl -X DELETE http://localhost:8080/api/users/1
```

## 🔧 Configuration

Les variables d'environnement importantes :

- `DB_HOST` : Hôte de la base de données
- `DB_DATABASE` : Nom de la base de données
- `SERVICE_NAME` : Nom du service pour les logs

## 🌐 Réseau Docker

Tous les services communiquent via le réseau Docker `microservices` permettant la résolution des noms de containers.

## 📊 Monitoring

Utilisez les endpoints `/health` pour surveiller l'état des services :

- User Service : http://localhost:8080/api/health
- Account Service : http://localhost:8081/api/health