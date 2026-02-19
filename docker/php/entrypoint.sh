#!/bin/sh
set -e

# Copier les assets compilés vers le volume partagé avec Nginx
if [ -d /var/www/html/public/assets ]; then
    cp -rf /var/www/html/public/assets/. /mnt/assets/
fi

# Générer les images manquantes pour les personnages de la BDD initiale
php bin/console app:generate-personnage-images --no-interaction || true

exec "$@"
