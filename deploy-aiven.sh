#!/bin/bash

# Install dependencies
composer install --no-dev --optimize-autoloader

# Create uploads directory if it doesn't exist
mkdir -p uploads

# Set proper permissions
chmod -R 755 uploads

# Run database migrations (if any)
# php bin/migrate.php

# Start the application
php -S 0.0.0.0:8080
