#!/bin/bash

echo "🗄️ Initialisation des bases de données microservices..."

# Attendre que les bases de données soient prêtes
echo "⏳ Attente des bases de données..."
sleep 10

echo "📊 Initialisation de la base de données User Service..."
docker exec user_service php artisan migrate:fresh --force

echo "📊 Initialisation de la base de données Account Service..."
docker exec account_service php artisan migrate:fresh --force

echo "✅ Bases de données initialisées!"

# Vérification des tables créées
echo "🔍 Vérification des tables User Service:"
docker exec user_db mysql -u laravel -plaravel user_service -e "SHOW TABLES;"

echo "🔍 Vérification des tables Account Service:"
docker exec account_db mysql -u laravel -plaravel account_service -e "SHOW TABLES;"

echo "✅ Configuration terminée!"