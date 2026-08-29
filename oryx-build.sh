#!/bin/bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies and build assets
if [ -f "package.json" ]; then
    npm install
    npm run build
fi
