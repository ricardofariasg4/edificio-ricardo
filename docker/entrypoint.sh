#!/bin/bash
set -e

if [ ! -f "vendor/autoload.php" ]; then
    echo "Instalando dependências do Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ ! -d "node_modules" ]; then
    echo "Instalando dependências do Node.js..."
    npm install
fi

if [ ! -f ".env" ]; then
    echo "Criando .env a partir do .env.example..."
    cp .env.example .env
    php artisan key:generate --no-interaction
fi

exec "$@"
