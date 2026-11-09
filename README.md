# FantasyRealm Online — Guide de déploiement

Application Symfony 8.0 de gestion de personnages MMORPG pour PixelVerse Studios.

---

## Comptes par défaut (à changer immédiatement en production)

| Rôle | Pseudo | Email | Mot de passe |
|---|---|---|---|
| Administrateur | `admin` | `admin@pixelverse.com` | `Admin@12345!` |
| Employé | `moderateur1` | `moderateur@pixelverse.com` | `Employe@1234!` |
| Utilisateur test | `chevalier_noir` | `chevalier@test.com` | `User@12345678!` |

---

## Déploiement en développement (local)

### Prérequis
- Docker Desktop installé et démarré
- Git

### Lancement

```bash
# 1. Cloner le dépôt
git clone <url-du-depot> fantasy_realm
cd fantasy_realm

# 2. Démarrer les conteneurs
docker-compose up -d --build

# 3. Installer les dépendances de développement (outils de débogage de Symfony)
#    L'image Docker est construite pour la production, sans ces outils, alors
#    qu'en local l'application tourne en mode dev : sans cette étape, toutes
#    les pages renvoient une erreur 500.
docker-compose exec php composer install

# 4. Vider le cache si besoin
docker-compose exec php php bin/console cache:clear
```

**URLs disponibles :**
- Application : http://localhost:8090
- Mailpit (emails de test) : http://localhost:8026
- MySQL : localhost:3308
- MongoDB : localhost:27019

---

## Déploiement en production

### Prérequis serveur
- Docker Engine 24+ et Docker Compose v2
- Un nom de domaine pointant vers l'IP du serveur
- Certificat SSL (Let's Encrypt recommandé)
- Ports 80 et 443 ouverts dans le pare-feu

### Étape 1 — Copier les fichiers sur le serveur

```bash
# Depuis votre machine locale, envoyer le projet sur le serveur
scp -r ./fantasy_realm user@votre-serveur:/var/www/fantasy_realm

# Ou via Git
ssh user@votre-serveur
git clone <url-du-depot> /var/www/fantasy_realm
cd /var/www/fantasy_realm
```

### Étape 2 — Créer le fichier `.env.prod`

Créez `/var/www/fantasy_realm/.env.prod` avec des valeurs sécurisées.
**Ce fichier ne doit jamais être commité dans Git.**

```bash
# Générer un APP_SECRET sécurisé
openssl rand -hex 32
```

```dotenv
# .env.prod — NE PAS COMMITER

APP_SECRET=<résultat de openssl rand -hex 32>

# Base de données MySQL
MYSQL_ROOT_PASSWORD=<mot_de_passe_root_fort>
MYSQL_DATABASE=fantasyrealm_db
MYSQL_USER=fantasyrealm_user
MYSQL_PASSWORD=<mot_de_passe_db_fort>

# MongoDB
MONGO_USER=fantasyrealm_mongo
MONGO_PASSWORD=<mot_de_passe_mongo_fort>
MONGODB_DB=fantasyrealm_logs

# Mailer — exemples de services SMTP
# Gmail :      smtp://utilisateur:mot_de_passe@smtp.gmail.com:587
# SMTP2GO :    smtp://utilisateur:mot_de_passe@mail.smtp2go.com:587
# Mailgun :    smtp://utilisateur:mot_de_passe@smtp.mailgun.org:587
MAILER_DSN=smtp://utilisateur:mot_de_passe@smtp.votre-fournisseur.com:587
```

> **Règles pour les mots de passe :** minimum 20 caractères, majuscules, minuscules, chiffres et caractères spéciaux.

### Étape 3 — Configurer le domaine dans Nginx

Éditez `docker/nginx/prod.conf` et remplacez `votre-domaine.com` par votre vrai domaine :

```bash
sed -i 's/votre-domaine.com/fantasyrealmstudios.com/g' docker/nginx/prod.conf
```

### Étape 4 — Installer le certificat SSL

**Option A — Let's Encrypt (recommandé, gratuit) :**

Certbot a besoin du port 80 libre pour valider le domaine. Si Docker Nginx tourne déjà, arrêtez-le d'abord.

```bash
# Installer Certbot sur le serveur hôte
apt install certbot

# Arrêter Nginx si déjà démarré (libère le port 80 pour Certbot)
docker stop fantasyrealm_nginx 2>/dev/null || true

# Générer le certificat
certbot certonly --standalone -d votre-domaine.com

# Copier les certificats dans le dossier du projet
mkdir -p /var/www/fantasy_realm/ssl
cp /etc/letsencrypt/live/votre-domaine.com/fullchain.pem /var/www/fantasy_realm/ssl/
cp /etc/letsencrypt/live/votre-domaine.com/privkey.pem /var/www/fantasy_realm/ssl/
chmod 644 /var/www/fantasy_realm/ssl/*.pem
```

Montez ensuite le dossier `ssl/` dans le volume `ssl_certs` en ajoutant dans `docker-compose.prod.yml` sous le service `nginx` :

```yaml
    volumes:
      - ./ssl:/etc/nginx/ssl:ro
```

> **Si l'erreur "unauthorized" apparaît :** le port 80 est bloqué ou redirigé. Vérifiez que votre pare-feu autorise le port 80 entrant (`ufw allow 80`) et qu'aucun autre service n'écoute sur ce port (`ss -tlnp | grep :80`).

**Option B — Certificat existant :** copiez `fullchain.pem` et `privkey.pem` dans `./ssl/`.

### Étape 5 — Lancer les conteneurs de production

```bash
cd /var/www/fantasy_realm

docker-compose -f docker-compose.prod.yml --env-file .env.prod up -d --build
```

### Étape 6 — Vérifier le démarrage

```bash
# Vérifier que tous les conteneurs sont UP
docker-compose -f docker-compose.prod.yml ps

# Vérifier les logs PHP
docker logs fantasyrealm_php --tail=50

# Vérifier les logs Nginx
docker logs fantasyrealm_nginx --tail=50

# Tester la connexion MongoDB
docker exec fantasyrealm_php php -r "
require 'vendor/autoload.php';
\$url = getenv('MONGODB_URL');
try {
    \$c = new MongoDB\Client(\$url);
    \$c->selectDatabase('admin')->command(['ping' => 1]);
    echo 'MongoDB OK' . PHP_EOL;
} catch (\Exception \$e) {
    echo 'MongoDB ERREUR : ' . \$e->getMessage() . PHP_EOL;
}"

# Vérifier que Symfony est en mode prod
docker exec fantasyrealm_php php bin/console about
```

### Étape 7 — Changer les mots de passe par défaut

Connectez-vous sur l'application avec le compte admin (`admin@pixelverse.com` / `Admin@12345!`) et :

1. **Changer le mot de passe admin** : espace admin → compte admin → modifier
2. **Supprimer ou modifier le compte employé de test** (`moderateur1`)
3. **Supprimer les comptes utilisateurs de test** (`chevalier_noir`, `elfe_sylvain`, `mage_arcane`, etc.)

---

## Différences dev / prod

| Paramètre | Développement | Production |
|---|---|---|
| `APP_ENV` | `dev` | `prod` |
| `MAILER_DSN` | `smtp://mailpit:1025` (Mailpit local) | SMTP réel (Gmail, Mailgun…) |
| Ports MySQL/MongoDB | Exposés (3308, 27019) | Non exposés |
| Code source | Monté en volume (rechargement à chaud) | Copié dans l'image |
| Authentification MongoDB | Aucune | Login/mot de passe obligatoires |
| HTTPS | Non | Oui (port 443 + redirect 80→443) |
| Cache Symfony | `dev` (rechargé automatiquement) | `prod` (compilé à la build) |
| Logs Symfony | Verbeux | Erreurs uniquement |

---

## Commandes utiles en production

```bash
# Vider et reconstruire le cache Symfony
docker exec fantasyrealm_php php bin/console cache:clear

# Voir les logs en direct
docker logs -f fantasyrealm_php
docker logs -f fantasyrealm_nginx

# Mettre à jour l'application (redéployer)
git pull
docker-compose -f docker-compose.prod.yml --env-file .env.prod up -d --build

# Sauvegarder la base de données MySQL
docker exec fantasyrealm_mysql mysqldump \
  -u fantasyrealm_user -p<MYSQL_PASSWORD> fantasyrealm_db \
  > backup_$(date +%Y%m%d).sql

# Sauvegarder les logs MongoDB
docker exec fantasyrealm_mongodb mongodump \
  --username <MONGO_USER> --password <MONGO_PASSWORD> \
  --authenticationDatabase admin \
  --db fantasyrealm_logs --out /tmp/mongodump
docker cp fantasyrealm_mongodb:/tmp/mongodump ./mongodump_$(date +%Y%m%d)

# Redémarrer un conteneur sans tout rebuilder
docker restart fantasyrealm_php

# Arrêter proprement
docker-compose -f docker-compose.prod.yml down
```

---

## Renouvellement automatique SSL (Let's Encrypt)

Ajoutez une tâche cron sur le serveur hôte :

```bash
crontab -e
# Ajouter (remplacez votre-domaine.com par votre vrai domaine) :
0 3 * * 0 docker stop fantasyrealm_nginx && certbot renew --quiet --standalone && cp /etc/letsencrypt/live/votre-domaine.com/fullchain.pem /var/www/fantasy_realm/ssl/ && cp /etc/letsencrypt/live/votre-domaine.com/privkey.pem /var/www/fantasy_realm/ssl/ && docker start fantasyrealm_nginx
```

---

## Structure des fichiers importants

```
fantasy_realm/
├── database/
│   ├── 01-schema.sql          # Schéma BDD (créé automatiquement au 1er démarrage)
│   ├── 02-data.sql            # Données initiales (rôles + comptes par défaut)
│   └── 03-seed-images.sql     # Images de démonstration
├── docker/
│   ├── nginx/
│   │   ├── default.conf       # Config Nginx développement
│   │   └── prod.conf          # Config Nginx production (HTTPS)
│   └── php/
│       ├── Dockerfile         # Image PHP 8.4-FPM + extensions
│       └── php-dev.ini        # Config PHP
├── docker-compose.yml         # Environnement développement
├── docker-compose.prod.yml    # Environnement production
├── .env                       # Variables par défaut (commitées)
├── .env.local                 # Surcharges locales (non commitées)
└── .env.prod                  # Variables de production (NON commitées — à créer)
```

---

## Variables d'environnement — référence complète

| Variable | Description | Exemple prod |
|---|---|---|
| `APP_SECRET` | Clé secrète Symfony (32 octets hex) | `openssl rand -hex 32` |
| `APP_ENV` | Environnement (`dev` ou `prod`) | `prod` |
| `DATABASE_URL` | URL complète MySQL | Construite automatiquement |
| `MYSQL_ROOT_PASSWORD` | Mot de passe root MySQL | `X7#mK2@pL9!qR...` |
| `MYSQL_DATABASE` | Nom de la base | `fantasyrealm_db` |
| `MYSQL_USER` | Utilisateur MySQL applicatif | `fantasyrealm_user` |
| `MYSQL_PASSWORD` | Mot de passe MySQL applicatif | `Z3$nP8&wV5!kQ...` |
| `MONGO_USER` | Utilisateur MongoDB | `fantasyrealm_mongo` |
| `MONGO_PASSWORD` | Mot de passe MongoDB | `Y4@oW7#xU6!jS...` |
| `MONGODB_DB` | Nom de la base MongoDB | `fantasyrealm_logs` |
| `MAILER_DSN` | DSN du serveur SMTP | `smtp://user:pass@host:587` |

---

## Liens et livrables

| Livrable | Lien |
|---|---|
| Application déployée | `[À COMPLÉTER — ex. https://fantasyrealm.app]` |
| Dépôt GitHub public | `[À COMPLÉTER — ex. https://github.com/user/fantasy_realm]` |
| Outil de gestion de projet | `[À COMPLÉTER — ex. https://trello.com/b/...] ` |
| Documentation (`docs/`) | Voir dossier [`docs/`](docs/) à la racine du projet |
