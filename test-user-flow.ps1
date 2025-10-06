# Script PowerShell pour tester le flux complet de création d'utilisateur
Write-Host "Test du flux complet User -> Account via RabbitMQ" -ForegroundColor Green

# Créer un utilisateur test
$userData = @{
    name = "Test User $(Get-Date -Format 'HHmm')"
    email = "test.$(Get-Date -Format 'HHmmss')@example.com"
    password = "password123"
    first_name = "Test"
}

$body = $userData | ConvertTo-Json
Write-Host "Création de l'utilisateur: $($userData.name)" -ForegroundColor Yellow

try {
    $response = Invoke-RestMethod -Uri "http://localhost:8082/api/users" -Method POST -Headers @{"Content-Type"="application/json"} -Body $body
    Write-Host "✅ Utilisateur créé avec saga_id: $($response.data.saga_id)" -ForegroundColor Green
    
    # Attendre 5 secondes pour le traitement RabbitMQ
    Write-Host "Attente du traitement RabbitMQ (5 secondes)..." -ForegroundColor Cyan
    Start-Sleep -Seconds 5
    
    # Vérifier les données
    Write-Host "Vérification des données..." -ForegroundColor Yellow
    
    # Derniers utilisateurs
    Write-Host "`n=== UTILISATEURS ===" -ForegroundColor Magenta
    docker-compose exec user-db mysql -u laravel -p"laravel" user_service -e "SELECT id, name, email, created_at FROM users ORDER BY created_at DESC LIMIT 2;"
    
    # Derniers comptes
    Write-Host "`n=== COMPTES ===" -ForegroundColor Magenta  
    docker-compose exec account-db mysql -u laravel -p"laravel" account_service -e "SELECT id, user_id, account_number, balance, status, created_at FROM accounts ORDER BY created_at DESC LIMIT 2;"
    
} catch {
    Write-Host "❌ Erreur lors de la création: $($_.Exception.Message)" -ForegroundColor Red
}