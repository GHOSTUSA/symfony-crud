# Script de test pour l'architecture microservices
Write-Host "🚀 Démarrage de l'architecture microservices..." -ForegroundColor Green

# Arrêter les conteneurs existants
Write-Host "📦 Arrêt des conteneurs existants..." -ForegroundColor Yellow
docker-compose down

# Construire et démarrer les services
Write-Host "🏗️ Construction et démarrage des services..." -ForegroundColor Yellow
docker-compose up --build -d

# Attendre que les services soient prêts
Write-Host "⏳ Attente du démarrage des services..." -ForegroundColor Yellow
Start-Sleep -Seconds 30

# Vérifier l'état des services
Write-Host "🔍 Vérification de l'état des services..." -ForegroundColor Yellow

Write-Host "📊 User Service Health Check:" -ForegroundColor Cyan
try {
    $userHealth = Invoke-RestMethod -Uri "http://localhost:8082/api/health" -Method Get
    Write-Host ($userHealth | ConvertTo-Json) -ForegroundColor Green
} catch {
    Write-Host "❌ Erreur lors de la vérification du User Service: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n📊 Account Service Health Check:" -ForegroundColor Cyan
try {
    $accountHealth = Invoke-RestMethod -Uri "http://localhost:8081/api/health" -Method Get
    Write-Host ($accountHealth | ConvertTo-Json) -ForegroundColor Green
} catch {
    Write-Host "❌ Erreur lors de la vérification de l'Account Service: $($_.Exception.Message)" -ForegroundColor Red
}

# Tests de base
Write-Host "`n🧪 Tests de base..." -ForegroundColor Yellow

Write-Host "1️⃣ Création d'un utilisateur (devrait créer automatiquement un compte bancaire):" -ForegroundColor Cyan

$userData = @{
    name = "John Doe"
    first_name = "John"
    email = "john@example.com"
    phone = "+33123456789"
    password = "password123"
} | ConvertTo-Json

try {
    $userResponse = Invoke-RestMethod -Uri "http://localhost:8082/api/users" -Method Post -Body $userData -ContentType "application/json"
    Write-Host ($userResponse | ConvertTo-Json) -ForegroundColor Green
    $userId = $userResponse.data.id

    if ($userId) {
        Write-Host "`n2️⃣ Vérification du compte bancaire créé automatiquement:" -ForegroundColor Cyan
        try {
            $accountResponse = Invoke-RestMethod -Uri "http://localhost:8081/api/accounts/user/$userId" -Method Get
            Write-Host ($accountResponse | ConvertTo-Json) -ForegroundColor Green
        } catch {
            Write-Host "❌ Compte bancaire non trouvé: $($_.Exception.Message)" -ForegroundColor Red
        }

        Write-Host "`n3️⃣ Liste de tous les comptes bancaires:" -ForegroundColor Cyan
        try {
            $allAccounts = Invoke-RestMethod -Uri "http://localhost:8081/api/accounts" -Method Get
            Write-Host ($allAccounts | ConvertTo-Json) -ForegroundColor Green
        } catch {
            Write-Host "❌ Erreur lors de la récupération des comptes: $($_.Exception.Message)" -ForegroundColor Red
        }

        Write-Host "`n4️⃣ Suppression de l'utilisateur (devrait supprimer le compte bancaire):" -ForegroundColor Cyan
        try {
            $deleteResponse = Invoke-RestMethod -Uri "http://localhost:8082/api/users/$userId" -Method Delete
            Write-Host ($deleteResponse | ConvertTo-Json) -ForegroundColor Green
        } catch {
            Write-Host "❌ Erreur lors de la suppression: $($_.Exception.Message)" -ForegroundColor Red
        }

        Write-Host "`n5️⃣ Vérification que le compte bancaire a été supprimé:" -ForegroundColor Cyan
        try {
            $checkAccount = Invoke-RestMethod -Uri "http://localhost:8081/api/accounts/user/$userId" -Method Get
            Write-Host ($checkAccount | ConvertTo-Json) -ForegroundColor Yellow
        } catch {
            Write-Host "✅ Compte bancaire correctement supprimé" -ForegroundColor Green
        }
    }
} catch {
    Write-Host "❌ Erreur lors de la création de l'utilisateur: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n✅ Tests terminés!" -ForegroundColor Green
Write-Host "🌐 Services disponibles:" -ForegroundColor Cyan
Write-Host "   - User Service: http://localhost:8080/api/" -ForegroundColor White
Write-Host "   - Account Service: http://localhost:8081/api/" -ForegroundColor White