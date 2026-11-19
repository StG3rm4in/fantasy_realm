# FantasyRealm Online — Guide de déploiement

Application Symfony 8.0 de gestion de personnages MMORPG pour PixelVerse Studios.

---

## Comptes du jeu d'essai (environnement local uniquement)

Ces mots de passe ne valent qu'en local. En production, ces comptes ont
d'autres mots de passe, communiqués au jury dans la copie.

| Rôle | Pseudo | Email | Mot de passe |
|---|---|---|---|
| Administrateur | `admin` | `admin@example.com` | `Admin@12345!` |
| Employé | `moderateur1` | `moderateur@example.com` | `Employe@1234!` |
| Utilisateur test | `chevalier_noir` | `chevalier@example.com` | `User@12345678!` |

---

## Déploiement en développement (local)

### Prérequis
- Docker Desktop installé et démarré
- Git
- Ports libres sur la machine : 8090 (site), 3308 (MySQL), 27019 (MongoDB),
  8026 et 1026 (Mailpit). Un MySQL déjà installé sur le port 3308 (XAMPP par
  exemple) empêche la base Docker de démarrer.

### Lancement

```bash
# 1. Cloner le dépôt
git clone <url-du-depot> fantasy_realm
cd fantasy_realm

# 2. Démarrer les conteneurs
docker compose up -d --build

# 3. Installer les dépendances de développement (outils de débogage de Symfony)
#    L'image Docker est construite pour la production, sans ces outils, alors
#    qu'en local l'application tourne en mode dev : sans cette étape, toutes
#    les pages renvoient une erreur 500.
docker compose exec php composer install

# 4. Vider le cache si besoin
docker compose exec php php bin/console cache:clear
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

# Expéditeur des mails (adresse autorisée par le fournisseur SMTP)
# et destinataire des messages du formulaire de contact
MAILER_FROM=noreply@votre-domaine.com
CONTACT_EMAIL=contact@votre-domaine.com
```

> Sans `MAILER_FROM` et `CONTACT_EMAIL`, `docker-compose.prod.yml` transmet des
> adresses vides à l'application : aucun mail ne part (inscription, contact,
> modération).

> **Règles pour ces mots de passe de service :** au moins 32 caractères, uniquement
> des lettres et des chiffres. Ils sont insérés dans `DATABASE_URL` et `MONGODB_URL`
> (`docker-compose.prod.yml`), où `@`, `:`, `/`, `#`, `?` ou `%` risquent de casser l'URL, et
> Docker Compose interprète `$`. La longueur remplace les caractères spéciaux.
> Pour en générer un : `openssl rand -base64 48 | tr -dc 'A-Za-z0-9' | head -c 32`

### Étape 3 — Configurer le domaine dans Nginx

Éditez `docker/nginx/prod.conf` et remplacez `votre-domaine.com` par votre vrai domaine :

```bash
sed -i 's/votre-domaine.com/fantasyrealm.app/g' docker/nginx/prod.conf
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

`docker-compose.prod.yml` monte déjà ce dossier dans Nginx (`./ssl:/etc/nginx/ssl:ro`).
Il est exclu de l'image PHP par `.dockerignore` : seul Nginx a besoin de la clé privée.

> **Si l'erreur "unauthorized" apparaît :** le port 80 est bloqué ou redirigé. Vérifiez que votre pare-feu autorise le port 80 entrant (`ufw allow 80`) et qu'aucun autre service n'écoute sur ce port (`ss -tlnp | grep :80`).

**Option B — Certificat existant :** copiez `fullchain.pem` et `privkey.pem` dans `./ssl/`.

### Étape 5 — Lancer les conteneurs de production

```bash
cd /var/www/fantasy_realm

docker compose -f docker-compose.prod.yml --env-file .env.prod up -d --build
```

### Étape 6 — Vérifier le démarrage

```bash
# Vérifier que tous les conteneurs sont UP
docker compose -f docker-compose.prod.yml --env-file .env.prod ps

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

Les comptes du jeu d'essai arrivent en production avec les mots de passe du
README : il faut les changer avant d'ouvrir le site. L'application n'a pas
d'écran pour changer son propre mot de passe, et « mot de passe oublié » est
refusé aux comptes employé et administrateur. On passe donc par la base :

```bash
# 1. Calculer le hash du nouveau mot de passe (saisie masquée)
docker exec -it fantasyrealm_php php bin/console security:hash-password

# 2. Ouvrir le client MySQL (le mot de passe MYSQL_PASSWORD est demandé)
docker exec -it fantasyrealm_mysql mysql -u fantasyrealm_user -p fantasyrealm_db
```

Puis, dans le client MySQL, pour chaque compte :

```sql
UPDATE utilisateur SET password = '<hash affiché à l''étape 1>' WHERE pseudo = 'admin';
```

> Le hash contient des `$` : tapez la requête dans le client MySQL, pas dans une
> commande shell entre guillemets doubles, où le shell remplacerait `$2y`, `$13`…
> par des variables vides et enregistrerait un hash faux.

Les comptes de test inutiles se suppriment ensuite depuis l'application :
les utilisateurs dans l'espace employé, l'employé `moderateur1` dans l'espace
administrateur.

---

## Différences dev / prod

| Paramètre | Développement | Production |
|---|---|---|
| `APP_ENV` | `dev` | `prod` |
| `MAILER_DSN` | `smtp://mailpit:1025` (Mailpit local) | SMTP réel (Brevo pour ce projet) |
| Ports MySQL/MongoDB | Exposés (3308, 27019) | Non exposés |
| Code source | Monté en volume (rechargement à chaud) | Copié dans l'image |
| Authentification MongoDB | Aucune | Login/mot de passe obligatoires |
| HTTPS | Non | Oui (port 443 + redirect 80→443) |
| Cache Symfony | `dev` (rechargé automatiquement) | `prod`, gardé dans le volume `var_data` : à vider après chaque mise à jour |
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
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d --build
# var/ est un volume qui survit au redéploiement : sans cette ligne, Symfony
# garde le cache de l'ancienne version (templates et routes inchangés)
docker exec -u www-data fantasyrealm_php php bin/console cache:clear

# Sauvegarder la base de données MySQL (identifiants lus dans le conteneur)
docker exec fantasyrealm_mysql sh -c \
  'mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --single-transaction --no-tablespaces "$MYSQL_DATABASE"' \
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
docker compose -f docker-compose.prod.yml --env-file .env.prod down
```

---

## Renouvellement automatique SSL (Let's Encrypt)

Le paquet `certbot` installe un minuteur systemd (`certbot.timer`) qui lance
`certbot renew` deux fois par jour. En mode `standalone`, Certbot a besoin du
port 80 : trois scripts, appelés automatiquement, libèrent le port, copient le
nouveau certificat et relancent Nginx.

```bash
# Avant le renouvellement : libérer le port 80
cat > /etc/letsencrypt/renewal-hooks/pre/stop-nginx.sh <<'EOF'
#!/bin/sh
docker stop fantasyrealm_nginx
EOF

# Si le certificat a été renouvelé : le copier dans le projet
cat > /etc/letsencrypt/renewal-hooks/deploy/copy-certs.sh <<'EOF'
#!/bin/sh
cp /etc/letsencrypt/live/votre-domaine.com/fullchain.pem /etc/letsencrypt/live/votre-domaine.com/privkey.pem /var/www/fantasy_realm/ssl/
EOF

# Après, dans tous les cas : relancer Nginx
cat > /etc/letsencrypt/renewal-hooks/post/start-nginx.sh <<'EOF'
#!/bin/sh
docker start fantasyrealm_nginx
EOF

chmod +x /etc/letsencrypt/renewal-hooks/*/*.sh
systemctl is-active certbot.timer   # doit répondre « active »
certbot renew --dry-run             # simulation complète, sans rien changer
```

---

## Structure des fichiers importants

```
fantasy_realm/
├── database/
│   ├── 01-schema.sql          # Schéma BDD (créé automatiquement au 1er démarrage)
│   ├── 02-data.sql            # Données initiales (rôles + comptes par défaut)
│   ├── 03-seed-images.sql     # Images de démonstration (chargées en local seulement)
│   └── generate_seed_images.php  # Script qui a produit 03-seed-images.sql
├── docker/
│   ├── nginx/
│   │   ├── default.conf       # Config Nginx développement
│   │   └── prod.conf          # Config Nginx production (HTTPS)
│   └── php/
│       ├── Dockerfile         # Image PHP 8.4-FPM + extensions
│       ├── entrypoint.sh      # Démarrage : copie des assets compilés pour Nginx
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
| `MYSQL_ROOT_PASSWORD` | Mot de passe root MySQL | 32 lettres et chiffres (voir l'étape 2) |
| `MYSQL_DATABASE` | Nom de la base | `fantasyrealm_db` |
| `MYSQL_USER` | Utilisateur MySQL applicatif | `fantasyrealm_user` |
| `MYSQL_PASSWORD` | Mot de passe MySQL applicatif | 32 lettres et chiffres |
| `MONGO_USER` | Utilisateur MongoDB | `fantasyrealm_mongo` |
| `MONGO_PASSWORD` | Mot de passe MongoDB | 32 lettres et chiffres |
| `MONGODB_DB` | Nom de la base MongoDB | `fantasyrealm_logs` |
| `MAILER_DSN` | DSN du serveur SMTP | `smtp://user:pass@host:587` |
| `MAILER_FROM` | Adresse d'expédition des mails | `noreply@votre-domaine.com` |
| `CONTACT_EMAIL` | Destinataire du formulaire de contact | `contact@votre-domaine.com` |

---

## Liens et livrables

| Livrable | Lien |
|---|---|
| Application déployée | https://fantasyrealm.app |
| Dépôt GitHub public | https://github.com/StG3rm4in/fantasy_realm |
| Outil de gestion de projet (Trello) | https://trello.com/b/y8GYJcye/fantasyrealm-ecf-d%C3%A9veloppeur-web-et-web-mobile |
| Documentation (`docs/`) | Voir dossier [`docs/`](docs/) à la racine du projet |
