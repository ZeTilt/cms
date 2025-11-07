#!/bin/bash
# Script pour générer les clés VAPID pour les notifications push
# Usage: ./generate-vapid-keys.sh

echo "🔑 Génération des clés VAPID pour les notifications push..."
echo ""

# Vérifier si Node.js est installé
if ! command -v npx &> /dev/null; then
    echo "❌ Node.js n'est pas installé!"
    echo "   Installez Node.js depuis https://nodejs.org/"
    exit 1
fi

echo "📦 Génération des clés avec web-push..."
echo ""

# Générer les clés
KEYS=$(npx web-push generate-vapid-keys --json 2>/dev/null)

if [ $? -eq 0 ]; then
    PUBLIC_KEY=$(echo "$KEYS" | grep -o '"publicKey":"[^"]*"' | cut -d'"' -f4)
    PRIVATE_KEY=$(echo "$KEYS" | grep -o '"privateKey":"[^"]*"' | cut -d'"' -f4)

    echo "✅ Clés générées avec succès!"
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "📋 Ajoutez ces lignes dans votre .env.prod.local :"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "###> Web Push Notifications ###"
    echo "VAPID_PUBLIC_KEY=$PUBLIC_KEY"
    echo "VAPID_PRIVATE_KEY=$PRIVATE_KEY"
    echo "VAPID_SUBJECT=mailto:contact@plongee-venetes.fr"
    echo "###< Web Push Notifications ###"
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "⚠️  IMPORTANT:"
    echo "   - Ne commitez JAMAIS ces clés dans Git"
    echo "   - Gardez-les en sécurité"
    echo "   - Utilisez les mêmes clés en développement et production"
    echo ""
else
    echo "❌ Erreur lors de la génération des clés"
    exit 1
fi
