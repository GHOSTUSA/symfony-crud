# Initialisation des bases de données microservices
Write-Host "🗄️ Initialisation des bases de données microservices..." -ForegroundColor Green

# Attendre que les bases de données soient prêtes
Write-Host "⏳ Attente des bases de données..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

Write-Host "📊 Initialisation de la base de données User Service..." -ForegroundColor Cyan
docker exec user_service php artisan migrate:fresh --force

Write-Host "📊 Initialisation de la base de données Account Service..." -ForegroundColor Cyan
docker exec account_service php artisan migrate:fresh --force

Write-Host "✅ Bases de données initialisées!" -ForegroundColor Green

# Vérification des tables créées
Write-Host "🔍 Vérification des tables User Service:" -ForegroundColor Cyan
docker exec user_db mysql -u laravel -plaravel user_service -e "SHOW TABLES;"

Write-Host "🔍 Vérification des tables Account Service:" -ForegroundColor Cyan
docker exec account_db mysql -u laravel -plaravel account_service -e "SHOW TABLES;"

Write-Host "✅ Configuration terminée!" -ForegroundColor Green