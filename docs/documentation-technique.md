# Documentation technique — FantasyRealm Online

**Projet :** FantasyRealm Online — Application de gestion de personnages MMORPG
**Client :** PixelVerse Studios
**Version :** 1.0 — ECF Studi 2026

---

## 1. Réflexions technologiques initiales

### Choix du framework backend : Symfony 8 / PHP 8.4

Symfony a été retenu pour sa robustesse sur les applications métier complexes :
- **Sécurité intégrée** : CSRF, hachage de mots de passe (bcrypt), firewall configurable, hiérarchie de rôles
- **ORM Doctrine** : mapping objet-relationnel évitant le SQL manuel tout en permettant des requêtes optimisées
- **Système de templates Twig** : séparation claire logique / présentation
- **Mailer intégré** : gestion unifiée des emails (SMTP, null transport pour les tests)
- **Asset Mapper** : gestion des assets sans Node.js ni Webpack

### Choix de la base de données relationnelle : MySQL 8

MySQL a été préféré pour :
- **Données structurées** : entités fortement typées (utilisateurs, personnages, articles, commentaires)
- **Intégrité référentielle** : clés étrangères, contraintes CASCADE pour la suppression en cascade
- **Contraintes CHECK** : validation des valeurs de type "enum" directement en base

### Choix de la base NoSQL : MongoDB

MongoDB a été utilisé exclusivement pour les **logs d'activité** :
- Schéma flexible adapté aux événements hétérogènes
- Performances en écriture optimisées pour les logs
- Filtrage et pagination natifs
- Connexion via le driver PHP natif `ext-mongodb` + bibliothèque `mongodb/mongodb ^2.2`

### Choix de la conteneurisation : Docker + Docker Compose

- **Isolation** : chaque service dans son conteneur (PHP-FPM, Nginx, MySQL, MongoDB, Mailpit)
- **Reproductibilité** : même environnement en dev et en prod
- **Simplification du déploiement** : `docker compose up -d --build`

### Choix du frontend : Bootstrap 5 + JavaScript vanilla + Stimulus/Turbo

- **Bootstrap 5.3** via CDN : composants UI responsifs, thème dark natif
- **JavaScript vanilla** : validation formulaires, aperçu personnage canvas, étoiles interactives
- **Stimulus 3.2 + Turbo 7.3** : navigation SPA légère sans rechargement complet

---

## 2. Configuration de l'environnement

### Environnement de développement local (XAMPP)

| Service | Version | URL/Port |
|---|---|---|
| PHP | 8.4 | Ligne de commande |
| MySQL | 8.0 | localhost:3306 |
| Application | Symfony CLI | http://localhost |

Configuration : fichier `.env` (committé, valeurs dev par défaut)

### Environnement de développement Docker

```
docker-compose up -d --build
```

| Service | Image | Port hôte |
|---|---|---|
| Application | PHP 8.4-FPM + Nginx | http://localhost:8090 |
| MySQL | mysql:8.0 | localhost:3308 |
| MongoDB | mongo:latest | localhost:27019 |
| Mailpit (emails test) | axllent/mailpit | http://localhost:8026 |

Configuration : `.env` (base) + `.env.local` (surcharges Docker, non committé)

### Environnement de production

```
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d --build
```

Différences par rapport au dev :
- Code source **copié dans l'image** (pas de volume de code)
- `APP_ENV=prod` → cache compilé, logs réduits
- MongoDB avec **authentification obligatoire**
- Ports MySQL/MongoDB **non exposés** à l'extérieur
- HTTPS via Let's Encrypt (Certbot)
- Assets compilés automatiquement via entrypoint.sh

---

## 3. Modèle Conceptuel de Données (MCD)

```mermaid
erDiagram
    role {
        int id_role PK
        varchar libelle
    }

    utilisateur {
        int id_utilisateur PK
        varchar pseudo
        varchar mail
        varchar password
        boolean suspendu
        boolean motdepasse_a_modifier
        int id_role FK
    }

    personnage {
        int id_personnage PK
        varchar nom
        varchar genre
        varchar couleur_peau
        varchar couleur_yeux
        varchar couleur_cheveux
        varchar forme_yeux
        varchar forme_nez
        varchar forme_bouche
        boolean partage
        boolean autorise
        longblob image
        datetime date_creation
        int id_utilisateur FK
    }

    commentaire {
        int id_commentaire PK
        int note
        text commentaire
        varchar statut
        int id_personnage FK
        int id_utilisateur FK
    }

    article {
        int id_article PK
        varchar nom
        varchar type
        boolean actif
        longblob image
    }

    personnage_article {
        int id_personnage FK
        int id_article FK
    }

    role ||--o{ utilisateur : "possède"
    utilisateur ||--o{ personnage : "crée"
    personnage ||--o{ commentaire : "reçoit"
    utilisateur ||--o{ commentaire : "rédige"
    personnage }o--o{ article : "équipe"
```

### Contraintes notables

- `utilisateur.suspendu` : booléen — compte bloqué si `TRUE`
- `utilisateur.motdepasse_a_modifier` : force le changement au prochain login
- `personnage.autorise` : tri-état — `NULL` = en attente, `TRUE` = approuvé, `FALSE` = rejeté (supprimé)
- `personnage.partage` : `TRUE` = visible dans la galerie publique
- `commentaire.statut` : `en_attente` | `approuve` | `refuse` (CHECK en base)
- `article.type` : `vetement` | `armure` | `accessoire` | `arme` (CHECK en base)
- `personnage.genre` : `Masculin` | `Féminin` | `Autre` (CHECK en base)

---

## 4. Diagramme de cas d'utilisation

```mermaid
graph TD
    PUB([Visiteur public])
    USR([Utilisateur connecté])
    EMP([Employé])
    ADM([Administrateur])

    PUB --> A[Consulter la galerie]
    PUB --> B[Voir le détail d'un personnage]
    PUB --> C[Envoyer un message de contact]
    PUB --> D[S'inscrire]
    PUB --> E[Se connecter]

    USR --> F[Créer un personnage]
    USR --> G[Modifier son personnage]
    USR --> H[Dupliquer un personnage]
    USR --> I[Partager / rendre privé]
    USR --> J[Gérer les accessoires]
    USR --> K[Noter et commenter]
    USR --> L[Supprimer son compte RGPD]

    EMP --> M[Modérer les personnages]
    EMP --> N[Modérer les avis]
    EMP --> O[Gérer les articles]
    EMP --> P[Gérer les utilisateurs]
    EMP --> USR

    ADM --> Q[Gérer les employés]
    ADM --> R[Consulter les logs MongoDB]
    ADM --> EMP
```

---

## 5. Diagramme de séquence — Création et validation d'un personnage

```mermaid
sequenceDiagram
    actor Utilisateur
    participant Symfony
    participant MySQL
    actor Employé
    participant Mailer

    Utilisateur->>Symfony: POST /espace-utilisateur/personnage/nouveau
    Symfony->>Symfony: Validation formulaire (CSRF, champs requis)
    Symfony->>MySQL: INSERT INTO personnage (autorise=NULL, partage=FALSE)
    MySQL-->>Symfony: id_personnage
    Symfony->>MySQL: INSERT log MongoDB (action=creation)
    Symfony->>Symfony: Génère image SVG côté serveur
    Symfony->>MySQL: UPDATE personnage SET image=...
    Symfony-->>Utilisateur: Redirect /espace-utilisateur (flash "En attente")

    Note over Employé,Symfony: File de modération

    Employé->>Symfony: GET /espace-employe (liste personnages en attente)
    Symfony->>MySQL: SELECT personnage WHERE autorise IS NULL
    MySQL-->>Symfony: liste personnages
    Symfony-->>Employé: Affiche file de modération

    alt Approbation
        Employé->>Symfony: POST /espace-employe/personnage/{id}/approuver
        Symfony->>MySQL: UPDATE personnage SET autorise=TRUE
        Symfony->>Mailer: Envoi email "Personnage approuvé"
        Mailer-->>Utilisateur: Email de confirmation
        Symfony-->>Employé: Redirect (flash "Approuvé")
    else Rejet
        Employé->>Symfony: POST /espace-employe/personnage/{id}/rejeter (motif)
        Symfony->>Mailer: Envoi email "Personnage rejeté" avec motif
        Mailer-->>Utilisateur: Email avec motif de rejet
        Symfony->>MySQL: DELETE personnage WHERE id=...
        Symfony-->>Employé: Redirect (flash "Rejeté")
    end
```

---

## 6. Diagramme de séquence — Connexion et redirection selon le rôle

```mermaid
sequenceDiagram
    actor Utilisateur
    participant Symfony Security
    participant UserProvider
    participant UserChecker
    participant AuthSuccessHandler
    participant MySQL

    Utilisateur->>Symfony Security: POST /connexion (email, password, _csrf_token)
    Symfony Security->>Symfony Security: Validation token CSRF
    Symfony Security->>UserProvider: loadUserByIdentifier(email)
    UserProvider->>MySQL: SELECT utilisateur WHERE mail=?
    MySQL-->>UserProvider: Objet User
    UserProvider-->>Symfony Security: User

    Symfony Security->>Symfony Security: Vérification password (bcrypt)

    Symfony Security->>UserChecker: checkPreAuth(user)
    alt Compte suspendu
        UserChecker-->>Symfony Security: CustomUserMessageAccountStatusException
        Symfony Security-->>Utilisateur: Erreur "Compte suspendu"
    else Compte actif
        UserChecker-->>Symfony Security: OK
    end

    Symfony Security->>AuthSuccessHandler: onAuthenticationSuccess(request, token)
    AuthSuccessHandler->>AuthSuccessHandler: Vérification motdepasse_a_modifier

    alt motdepasse_a_modifier = TRUE
        AuthSuccessHandler-->>Utilisateur: Redirect /changement-mot-de-passe
    else Rôle ROLE_ADMIN
        AuthSuccessHandler-->>Utilisateur: Redirect /espace-admin
    else Rôle ROLE_EMPLOYEE
        AuthSuccessHandler-->>Utilisateur: Redirect /espace-employe
    else Rôle ROLE_USER
        AuthSuccessHandler-->>Utilisateur: Redirect /espace-utilisateur
    end
```

---

## 7. Architecture des services

| Service | Fichier | Responsabilité |
|---|---|---|
| `MailService` | `src/Service/MailService.php` | 5 types d'emails : contact, mot de passe oublié, personnage approuvé/rejeté, avis approuvé |
| `MongoLogService` | `src/Service/MongoLogService.php` | Écriture et lecture des logs dans MongoDB |
| `SvgPersonnageRenderer` | `src/Service/SvgPersonnageRenderer.php` | Génération SVG côté serveur (miroir PHP de `PersonnageRenderer.js`) |
| `PersonnageImageGenerator` | `src/Service/PersonnageImageGenerator.php` | Fallback GD pour la génération d'images PNG |

### Composants de sécurité

| Composant | Fichier | Rôle |
|---|---|---|
| `UserProvider` | `src/Security/UserProvider.php` | Chargement utilisateur depuis MySQL (colonnes françaises) |
| `UserChecker` | `src/Security/UserChecker.php` | Vérification suspension avant connexion |
| `AuthenticationSuccessHandler` | `src/Security/AuthenticationSuccessHandler.php` | Redirection post-login selon rôle + mot de passe à modifier |

---

## 8. Déploiement

Voir le fichier **`README.md`** à la racine pour les instructions complètes de déploiement local et production.

Fichiers de configuration clés :
- `docker-compose.yml` — Environnement de développement
- `docker-compose.prod.yml` — Environnement de production
- `docker/php/Dockerfile` — Image PHP 8.4-FPM avec extensions
- `docker/php/entrypoint.sh` — Script de démarrage (copie assets, génère images)
- `docker/nginx/default.conf` — Nginx développement
- `docker/nginx/prod.conf` — Nginx production (HTTPS, TLS 1.2/1.3)
- `database/01-schema.sql` — Schéma complet de la base de données
- `database/02-data.sql` — Données initiales (rôles, comptes, articles)
