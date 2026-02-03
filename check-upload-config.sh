#!/bin/bash

# Script de vérification de la configuration de l'upload d'images
# Usage: ./check-upload-config.sh

echo "🔍 Vérification de la configuration d'upload d'images..."
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Compteur d'erreurs
ERRORS=0

# 1. Vérifier le symlink storage
echo "1️⃣  Vérification du symlink storage..."
if [ -L "public/storage" ]; then
    echo -e "${GREEN}✓${NC} Symlink storage existe"
else
    echo -e "${RED}✗${NC} Symlink storage manquant"
    echo "   → Exécuter: php artisan storage:link"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 2. Vérifier les permissions sur storage
echo "2️⃣  Vérification des permissions storage..."
if [ -w "storage/app/public" ]; then
    echo -e "${GREEN}✓${NC} storage/app/public est accessible en écriture"
else
    echo -e "${RED}✗${NC} storage/app/public n'est pas accessible en écriture"
    echo "   → Exécuter: chmod -R 775 storage/app/public"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 3. Vérifier la configuration .env
echo "3️⃣  Vérification de la configuration .env..."
if [ -f ".env" ]; then
    echo -e "${GREEN}✓${NC} Fichier .env existe"
    
    # Vérifier FILESYSTEM_DISK
    if grep -q "FILESYSTEM_DISK=public" .env; then
        echo -e "${GREEN}✓${NC} FILESYSTEM_DISK=public configuré"
    else
        echo -e "${YELLOW}⚠${NC} FILESYSTEM_DISK non configuré ou différent"
        echo "   → Vérifier: FILESYSTEM_DISK=public dans .env"
    fi
    
    # Vérifier APP_URL
    if grep -q "APP_URL=" .env; then
        APP_URL=$(grep "APP_URL=" .env | cut -d '=' -f2)
        echo -e "${GREEN}✓${NC} APP_URL configuré: $APP_URL"
    else
        echo -e "${RED}✗${NC} APP_URL non configuré"
        ERRORS=$((ERRORS + 1))
    fi
else
    echo -e "${RED}✗${NC} Fichier .env manquant"
    echo "   → Copier: cp .env.example .env"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 4. Vérifier composer packages
echo "4️⃣  Vérification des packages Composer..."
if [ -f "vendor/autoload.php" ]; then
    echo -e "${GREEN}✓${NC} Vendor installé"
    
    # Vérifier Spatie Media Library
    if [ -d "vendor/spatie/laravel-medialibrary" ]; then
        echo -e "${GREEN}✓${NC} spatie/laravel-medialibrary installé"
    else
        echo -e "${RED}✗${NC} spatie/laravel-medialibrary manquant"
        echo "   → Exécuter: composer require spatie/laravel-medialibrary"
        ERRORS=$((ERRORS + 1))
    fi
else
    echo -e "${RED}✗${NC} Vendor non installé"
    echo "   → Exécuter: composer install"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 5. Vérifier les migrations
echo "5️⃣  Vérification de la base de données..."
if php artisan migrate:status > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} Base de données connectée"
    
    # Vérifier table media
    if php artisan migrate:status | grep -q "media"; then
        echo -e "${GREEN}✓${NC} Table media existe"
    else
        echo -e "${YELLOW}⚠${NC} Table media non trouvée dans les migrations"
        echo "   → Vérifier que les migrations de Spatie ont été exécutées"
    fi
else
    echo -e "${RED}✗${NC} Impossible de se connecter à la base de données"
    echo "   → Vérifier la configuration dans .env"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 6. Vérifier les limites PHP
echo "6️⃣  Vérification des limites PHP..."
UPLOAD_MAX=$(php -r "echo ini_get('upload_max_filesize');")
POST_MAX=$(php -r "echo ini_get('post_max_size');")

echo "   upload_max_filesize: $UPLOAD_MAX"
echo "   post_max_size: $POST_MAX"

# Convertir en bytes pour comparaison
UPLOAD_BYTES=$(php -r "echo return_bytes('$UPLOAD_MAX');")
REQUIRED_BYTES=5242880  # 5 Mo

if [ "$UPLOAD_BYTES" -ge "$REQUIRED_BYTES" ]; then
    echo -e "${GREEN}✓${NC} upload_max_filesize est suffisant (>= 5M)"
else
    echo -e "${YELLOW}⚠${NC} upload_max_filesize est inférieur à 5M"
    echo "   → Recommandé: upload_max_filesize = 10M dans php.ini"
fi
echo ""

# 7. Vérifier les fichiers critiques
echo "7️⃣  Vérification des fichiers critiques..."

FILES=(
    "app/Models/Post/Post.php"
    "app/Http/Controllers/Api/PostController.php"
    "app/Http/Requests/StorePostRequest.php"
    "app/Http/Requests/UpdatePostRequest.php"
    "app/Services/PostService.php"
    "app/Policies/PostPolicy.php"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $file"
    else
        echo -e "${RED}✗${NC} $file manquant"
        ERRORS=$((ERRORS + 1))
    fi
done
echo ""

# 8. Vérifier les routes API
echo "8️⃣  Vérification des routes API..."
if php artisan route:list --json | grep -q "api/posts"; then
    echo -e "${GREEN}✓${NC} Routes API posts configurées"
else
    echo -e "${RED}✗${NC} Routes API posts non trouvées"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# Résumé
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✅ Configuration OK - Prêt pour l'upload d'images${NC}"
else
    echo -e "${RED}❌ $ERRORS erreur(s) trouvée(s)${NC}"
    echo "   Veuillez corriger les erreurs ci-dessus"
fi
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Instructions de test
if [ $ERRORS -eq 0 ]; then
    echo "📝 Pour tester l'upload:"
    echo "   1. Démarrer le serveur: php artisan serve"
    echo "   2. Consulter: TEST_UPLOAD_IMAGES.md"
    echo "   3. Tester avec cURL ou Postman"
    echo ""
fi

exit $ERRORS
