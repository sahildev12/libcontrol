#!/usr/bin/env bash
# Run on Hostinger (or via: ssh -p 65002 u843330709@45.130.228.59 'bash -s' < scripts/pull-demo-and-hub.sh)
set -euo pipefail

for APP in \
  /home/u843330709/domains/libcontrol.in/public_html \
  /home/u843330709/domains/libcontrol.in/public_html/demo
do
  echo "=== $APP ==="
  cd "$APP"
  git fetch origin
  git reset --hard origin/main
  composer install --no-dev --optimize-autoloader --no-interaction
  php artisan migrate --force
  php artisan config:clear
  php artisan config:cache
  php artisan view:clear
  php artisan optimize
done

echo "Deployed $(git -C /home/u843330709/domains/libcontrol.in/public_html/demo rev-parse --short HEAD)"
