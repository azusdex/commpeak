#!/bin/bash

PROJECT_DIR="/var/www/app"
UPLOAD_DIR="/var/www/app/var/upload"

if [ ! -f "$PROJECT_DIR/composer.json" ]; then
  symfony new app --version=7.0 --webapp
  cd "$PROJECT_DIR"
  composer require twig
fi

if [ ! -d "$UPLOAD_DIR" ]; then
  mkdir -p "$UPLOAD_DIR"
  chmod 777 "$UPLOAD_DIR"
fi

#fuser -k 8000/tcp || true

cd "$PROJECT_DIR"
php -S 0.0.0.0:8000 -t public