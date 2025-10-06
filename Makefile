# Makefile pour l'architecture microservices

.PHONY: help build up down clean logs test init health restart

# Aide par défaut
help:
	@echo "=== Architecture Microservices - Commandes Disponibles ==="
	@echo ""
	@echo "🚀 Déploiement:"
	@echo "  make build     - Construire les images Docker"
	@echo "  make up        - Démarrer tous les services"
	@echo "  make start     - Build + Start + Init (recommandé)"
	@echo ""
	@echo "🔧 Gestion:"
	@echo "  make down      - Arrêter tous les services"
	@echo "  make restart   - Redémarrer tous les services"
	@echo "  make clean     - Nettoyer complètement (ATTENTION: supprime les données)"
	@echo ""
	@echo "🗄️ Base de données:"
	@echo "  make init      - Initialiser les bases de données"
	@echo "  make migrate   - Exécuter les migrations"
	@echo ""
	@echo "🔍 Monitoring:"
	@echo "  make logs      - Afficher les logs de tous les services"
	@echo "  make logs-user - Afficher les logs du service utilisateur"
	@echo "  make logs-account - Afficher les logs du service compte"
	@echo "  make health    - Vérifier l'état des services"
	@echo "  make ps        - Lister les conteneurs"
	@echo ""
	@echo "🧪 Tests:"
	@echo "  make test      - Exécuter les tests complets"
	@echo "  make test-quick - Tests rapides de connectivité"
	@echo ""
	@echo "📊 Utilitaires:"
	@echo "  make stats     - Statistiques des conteneurs"
	@echo "  make shell-user - Shell dans le conteneur utilisateur"
	@echo "  make shell-account - Shell dans le conteneur compte"

# Construction des images
build:
	@echo "🏗️ Construction des images Docker..."
	docker-compose build

# Démarrage des services
up:
	@echo "🚀 Démarrage des services..."
	docker-compose up -d

# Commande complète de démarrage
start: build up init health
	@echo "✅ Architecture microservices prête!"

# Arrêt des services
down:
	@echo "📦 Arrêt des services..."
	docker-compose down

# Redémarrage
restart: down up
	@echo "🔄 Services redémarrés"

# Nettoyage complet
clean:
	@echo "🧹 Nettoyage complet..."
	@echo "ATTENTION: Cette commande va supprimer TOUTES les données!"
	@read -p "Êtes-vous sûr? [y/N] " -n 1 -r; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker-compose down -v --remove-orphans; \
		docker system prune -f; \
		echo "✅ Nettoyage terminé"; \
	else \
		echo "❌ Nettoyage annulé"; \
	fi

# Initialisation des bases de données
init:
	@echo "🗄️ Initialisation des bases de données..."
	@echo "⏳ Attente du démarrage des services..."
	sleep 20
	docker exec user_service php artisan migrate:fresh --force || true
	docker exec account_service php artisan migrate:fresh --force || true
	@echo "✅ Bases de données initialisées"

# Migrations uniquement
migrate:
	@echo "📊 Exécution des migrations..."
	docker exec user_service php artisan migrate
	docker exec account_service php artisan migrate

# Affichage des logs
logs:
	docker-compose logs -f

logs-user:
	docker-compose logs -f user-service

logs-account:
	docker-compose logs -f account-service

# Vérification de santé
health:
	@echo "🔍 Vérification de l'état des services..."
	@echo "📊 User Service:"
	@curl -s http://localhost:8080/api/health | jq . || echo "❌ User Service non accessible"
	@echo ""
	@echo "📊 Account Service:"
	@curl -s http://localhost:8081/api/health | jq . || echo "❌ Account Service non accessible"

# Test rapide de connectivité
test-quick:
	@echo "🧪 Tests rapides de connectivité..."
	@curl -s http://localhost:8080/api/health > /dev/null && echo "✅ User Service OK" || echo "❌ User Service KO"
	@curl -s http://localhost:8081/api/health > /dev/null && echo "✅ Account Service OK" || echo "❌ Account Service KO"

# Tests complets
test:
	@echo "🧪 Exécution des tests complets..."
	@if command -v powershell > /dev/null; then \
		powershell -ExecutionPolicy Bypass -File ./test-microservices.ps1; \
	else \
		bash ./test-microservices.sh; \
	fi

# Statistiques des conteneurs
stats:
	docker stats --no-stream

# Liste des conteneurs
ps:
	docker-compose ps

# Shell dans les conteneurs
shell-user:
	docker exec -it user_service bash

shell-account:
	docker exec -it account_service bash

# Shell base de données
db-user:
	docker exec -it user_db mysql -u laravel -plaravel user_service

db-account:
	docker exec -it account_db mysql -u laravel -plaravel account_service

# Sauvegarde des bases de données
backup:
	@echo "💾 Sauvegarde des bases de données..."
	@mkdir -p backups
	docker exec user_db mysqldump -u laravel -plaravel user_service > backups/user_service_$(shell date +%Y%m%d_%H%M%S).sql
	docker exec account_db mysqldump -u laravel -plaravel account_service > backups/account_service_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "✅ Sauvegardes créées dans le dossier backups/"

# Restauration depuis une sauvegarde
restore-user:
	@echo "📥 Restauration de la base utilisateur..."
	@if [ -z "$(FILE)" ]; then echo "Usage: make restore-user FILE=path/to/backup.sql"; exit 1; fi
	docker exec -i user_db mysql -u laravel -plaravel user_service < $(FILE)

restore-account:
	@echo "📥 Restauration de la base comptes..."
	@if [ -z "$(FILE)" ]; then echo "Usage: make restore-account FILE=path/to/backup.sql"; exit 1; fi
	docker exec -i account_db mysql -u laravel -plaravel account_service < $(FILE)

# Configuration de développement
dev-setup:
	@echo "⚙️ Configuration de l'environnement de développement..."
	cp compte-bancaire-service/laravel-app/.env.example compte-bancaire-service/laravel-app/.env || true
	@echo "✅ Fichiers .env créés. Personnalisez-les si nécessaire."

# Affichage des URLs utiles
urls:
	@echo "🌐 URLs des services:"
	@echo "  User Service:    http://localhost:8080/api/"
	@echo "  Account Service: http://localhost:8081/api/"
	@echo "  Health Checks:"
	@echo "    User:    http://localhost:8080/api/health"
	@echo "    Account: http://localhost:8081/api/health"