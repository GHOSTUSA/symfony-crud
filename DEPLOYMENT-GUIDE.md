# Guide de Déploiement - Architecture Microservices

## 📋 Prérequis

- Docker et Docker Compose installés
- PowerShell (Windows) ou Bash (Linux/macOS)
- Au moins 4GB de RAM disponible
- Ports 8080, 8081, 3306, 3307 libres

## 🚀 Déploiement

### 1. Clonage et Préparation

```bash
# Aller dans le répertoire du projet
cd c:\Users\ethan\symfony-crud\symfony-crud

# Vérifier la structure
ls -la
```

### 2. Démarrage des Services

```powershell
# Avec PowerShell (Windows)
.\test-microservices.ps1
```

```bash
# Avec Bash (Linux/macOS)
./test-microservices.sh
```

**Ou manuellement :**

```bash
# Construire et démarrer
docker-compose up --build -d

# Initialiser les bases de données
./init-databases.ps1  # Windows
./init-databases.sh   # Linux/macOS
```

### 3. Vérification du Déploiement

Vérifiez que tous les services sont en cours d'exécution :

```bash
docker-compose ps
```

Vous devriez voir :
- `user_service` (running)
- `user_nginx` (running)
- `user_db` (running)
- `account_service` (running)
- `account_nginx` (running)
- `account_db` (running)

### 4. Tests de Santé

```bash
# User Service
curl http://localhost:8080/api/health

# Account Service
curl http://localhost:8081/api/health
```

## 🔧 Configuration

### Variables d'Environnement

Les services utilisent les variables suivantes :

**User Service :**
- `DB_HOST=user-db`
- `DB_DATABASE=user_service`
- `SERVICE_NAME=user-service`

**Account Service :**
- `DB_HOST=account-db`
- `DB_DATABASE=account_service`
- `SERVICE_NAME=account-service`

### Ports

| Service | Port Externe | Port Interne |
|---------|--------------|--------------|
| User Service | 8080 | 80 |
| Account Service | 8081 | 80 |
| User DB | 3306 | 3306 |
| Account DB | 3307 | 3306 |

## 🧪 Tests de l'Architecture

### Test Complet Automatisé

Le script `test-microservices.ps1` effectue :

1. ✅ Démarrage des services
2. ✅ Vérification de santé
3. ✅ Création d'utilisateur → Création automatique de compte bancaire
4. ✅ Vérification de la cohérence des données
5. ✅ Suppression d'utilisateur → Suppression automatique du compte
6. ✅ Vérification de la suppression en cascade

### Tests Manuels

#### Créer un Utilisateur

```bash
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Alice Dupont",
    "first_name": "Alice",
    "email": "alice@example.com",
    "phone": "+33123456789",
    "password": "securepassword"
  }'
```

#### Vérifier le Compte Bancaire Créé

```bash
# Récupérer l'ID utilisateur de la réponse précédente
curl http://localhost:8081/api/accounts/user/1
```

#### Lister Tous les Comptes

```bash
curl http://localhost:8081/api/accounts
```

#### Supprimer un Utilisateur

```bash
curl -X DELETE http://localhost:8080/api/users/1
```

#### Vérifier la Suppression du Compte

```bash
curl http://localhost:8081/api/accounts/user/1
# Devrait retourner 404 - Account not found
```

## 🐛 Dépannage

### Problèmes Courants

#### 1. Services ne démarrent pas

```bash
# Vérifier les logs
docker-compose logs user-service
docker-compose logs account-service
```

#### 2. Erreurs de base de données

```bash
# Redémarrer les services de base de données
docker-compose restart user-db account-db

# Réinitialiser les bases de données
docker exec user_service php artisan migrate:fresh --force
docker exec account_service php artisan migrate:fresh --force
```

#### 3. Problèmes de réseau entre services

```bash
# Vérifier le réseau Docker
docker network ls
docker network inspect symfony-crud_microservices
```

#### 4. Erreurs de communication inter-services

```bash
# Tester la connectivité interne
docker exec user_service ping account-nginx
docker exec account_service ping user-nginx
```

### Redémarrage Complet

```bash
# Arrêter et supprimer tous les conteneurs
docker-compose down -v

# Redémarrer from scratch
docker-compose up --build -d
```

### Nettoyage

```bash
# Supprimer tous les conteneurs et volumes
docker-compose down -v --remove-orphans

# Supprimer les images (optionnel)
docker system prune -af
```

## 📊 Monitoring

### Logs en Temps Réel

```bash
# Tous les services
docker-compose logs -f

# Service spécifique
docker-compose logs -f user-service
docker-compose logs -f account-service
```

### Surveillance des Performances

```bash
# Utilisation des ressources
docker stats

# Espace disque
docker system df
```

## 🔒 Sécurité

### Considérations de Production

1. **Variables d'environnement** : Ne jamais exposer les mots de passe en production
2. **Réseau** : Utiliser un réseau Docker isolé
3. **Volumes** : Sauvegarder régulièrement les volumes de base de données
4. **SSL/TLS** : Configurer HTTPS pour la production
5. **Authentification** : Implémenter JWT ou OAuth pour l'authentification inter-services

### Sauvegarde

```bash
# Sauvegarde des bases de données
docker exec user_db mysqldump -u laravel -plaravel user_service > user_backup.sql
docker exec account_db mysqldump -u laravel -plaravel account_service > account_backup.sql
```

## 📈 Évolutions Futures

### Fonctionnalités à Ajouter

1. **Service de Notification** : Notifications lors des créations/suppressions
2. **Service de Transaction** : Gestion des transactions financières
3. **API Gateway** : Point d'entrée unique pour tous les microservices
4. **Service Discovery** : Découverte automatique des services
5. **Load Balancer** : Répartition de charge
6. **Monitoring** : Prometheus + Grafana
7. **Logging Centralisé** : ELK Stack

### Architecture Événementielle

Remplacer les appels HTTP synchrones par :
- **Message Broker** : RabbitMQ ou Apache Kafka
- **Event Sourcing** : Traçabilité complète des événements
- **CQRS** : Séparation Command/Query