#!/bin/bash

echo "🚀 Démarrage de l'architecture microservices..."

# Arrêter les conteneurs existants
echo "📦 Arrêt des conteneurs existants..."
docker-compose down

# Construire et démarrer les services
echo "🏗️ Construction et démarrage des services..."
docker-compose up --build -d

# Attendre que les services soient prêts
echo "⏳ Attente du démarrage des services..."
sleep 30

# Vérifier l'état des services
echo "🔍 Vérification de l'état des services..."

echo "📊 User Service Health Check:"
curl -s http://localhost:8080/api/health | jq .

echo -e "\n📊 Account Service Health Check:"
curl -s http://localhost:8081/api/health | jq .

# Tests de base
echo -e "\n🧪 Tests de base..."

echo "1️⃣ Création d'un utilisateur (devrait créer automatiquement un compte bancaire):"
USER_RESPONSE=$(curl -s -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "first_name": "John", 
    "email": "john@example.com",
    "phone": "+33123456789",
    "password": "password123"
  }')

echo "$USER_RESPONSE" | jq .

USER_ID=$(echo "$USER_RESPONSE" | jq -r '.data.id')

if [ "$USER_ID" != "null" ] && [ "$USER_ID" != "" ]; then
    echo -e "\n2️⃣ Vérification du compte bancaire créé automatiquement:"
    curl -s http://localhost:8081/api/accounts/user/$USER_ID | jq .
    
    echo -e "\n3️⃣ Liste de tous les comptes bancaires:"
    curl -s http://localhost:8081/api/accounts | jq .
    
    echo -e "\n4️⃣ Suppression de l'utilisateur (devrait supprimer le compte bancaire):"
    curl -s -X DELETE http://localhost:8080/api/users/$USER_ID | jq .
    
    echo -e "\n5️⃣ Vérification que le compte bancaire a été supprimé:"
    curl -s http://localhost:8081/api/accounts/user/$USER_ID | jq .
else
    echo "❌ Erreur lors de la création de l'utilisateur"
fi

echo -e "\n✅ Tests terminés!"
echo "🌐 Services disponibles:"
echo "   - User Service: http://localhost:8080/api/"
echo "   - Account Service: http://localhost:8081/api/"