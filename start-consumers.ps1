# Script PowerShell pour démarrer les consumers RabbitMQ en arrière-plan
Write-Host "Démarrage des consumers RabbitMQ..." -ForegroundColor Green

# Démarrer le consumer User Service
Write-Host "Démarrage du consumer User Service (saga events)..." -ForegroundColor Yellow
Start-Process -NoNewWindow -FilePath "docker-compose" -ArgumentList "exec", "-d", "user-service", "php", "artisan", "saga:consume-events"

# Attendre 2 secondes
Start-Sleep -Seconds 2

# Démarrer le consumer Account Service  
Write-Host "Démarrage du consumer Account Service (account commands)..." -ForegroundColor Yellow
Start-Process -NoNewWindow -FilePath "docker-compose" -ArgumentList "exec", "-d", "account-service", "php", "artisan", "account:consume-commands"

Write-Host "Consumers démarrés en arrière-plan!" -ForegroundColor Green
Write-Host "Pour vérifier le statut, utilisez: docker-compose logs user-service account-service" -ForegroundColor Cyan