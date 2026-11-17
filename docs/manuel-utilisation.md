# Manuel d'utilisation — FantasyRealm Online

**Projet :** FantasyRealm Online — Application de gestion de personnages MMORPG
**Client :** PixelVerse Studios
**Version :** 1.0 — ECF Studi 2026

---

## 1. Présentation de l'application

**FantasyRealm Online** est une application web développée pour **PixelVerse Studios** permettant aux joueurs de créer, personnaliser et partager leurs personnages de jeu de rôle médiéval-fantastique (MMORPG).

### Fonctionnalités principales

- **Création de personnages** : apparence physique complète (genre, couleur de peau, yeux, cheveux, forme du visage) avec prévisualisation en temps réel
- **Galerie communautaire** : exploration des personnages partagés par la communauté, avec filtres (genre, date, pseudo)
- **Système d'accessoires** : équipement de vêtements, armures, accessoires et armes sur les personnages validés
- **Notation et avis** : système d'étoiles (1-5) et commentaires sur les personnages publics
- **Workflow de modération** : validation des personnages et des avis par une équipe d'employés
- **Espace d'administration** : gestion des employés et consultation des logs d'activité

### Accès à l'application

- **URL de production :** https://fantasyrealm.app
- **URL de développement :** http://localhost:8090
- **Navigateurs supportés :** Chrome 120+, Firefox 120+, Edge 120+, Safari 17+

---

## 2. Comptes de test

| Rôle | Pseudo | Email |
|---|---|---|
| **Administrateur** | `admin` | `admin@example.com` |
| **Employé** | `moderateur1` | `moderateur@example.com` |
| **Utilisateur** | `chevalier_noir` | `chevalier@example.com` |

> **Mots de passe :** ceux du site en production (https://fantasyrealm.app) ne
> sont pas publiés dans le dépôt. Ils sont communiqués au jury dans la copie.
> En local, les comptes du jeu d'essai utilisent les mots de passe indiqués
> dans le README.

---

## 3. Parcours Utilisateur

### 3.1 Inscription

1. Cliquer sur **"S'inscrire"** dans la navigation
2. Renseigner :
   - **Pseudo** (unique, 3-50 caractères)
   - **Email** (unique)
   - **Mot de passe** (minimum 12 caractères : 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial) — l'indicateur de force guide la saisie
   - **Confirmation du mot de passe**
3. Valider → redirection automatique vers l'espace utilisateur

### 3.2 Connexion

1. Cliquer sur **"Se connecter"**
2. Saisir email + mot de passe
3. Redirection automatique selon le rôle :
   - Utilisateur → `/espace-utilisateur`
   - Employé → `/espace-employe`
   - Administrateur → `/espace-admin`

> **Mot de passe oublié ?** Cliquer sur "Mot de passe oublié" → saisir email + pseudo → recevoir un mot de passe temporaire par email → se connecter → changer le mot de passe obligatoirement.

### 3.3 Création d'un personnage

1. Dans l'espace utilisateur, cliquer sur **"Créer un personnage"**
2. Renseigner les caractéristiques d'apparence :
   - **Nom** du personnage
   - **Genre** (Masculin / Féminin / Autre)
   - **Couleur de peau** (8 tons disponibles)
   - **Couleur des yeux** (7 couleurs)
   - **Couleur des cheveux** (11 couleurs)
   - **Forme des yeux, du nez, de la bouche**
3. La **prévisualisation** se met à jour en temps réel dans le canvas à droite
4. Cliquer sur **"Créer le personnage"**
5. Le personnage est créé avec le statut **"En attente de validation"** par un employé

### 3.4 Attente de validation

Après création, le personnage passe par une **file de modération employé** :
- **En attente** (`autorise = NULL`) : le personnage n'est pas accessible publiquement
- **Approuvé** (`autorise = true`) : le personnage peut être partagé et équipé
- **Rejeté** (`autorise = false`) : le personnage est définitivement supprimé, un email de notification avec le motif est envoyé

> En cas de rejet, vous recevrez un email expliquant le motif et pourrez en créer un nouveau.

### 3.5 Partager un personnage

Un personnage approuvé peut être rendu public :
1. Dans l'espace utilisateur, cliquer sur **"Partager"** sur la carte du personnage
2. Le personnage apparaît dans la **galerie publique** (`/personnages`)
3. Cliquer à nouveau sur **"Rendre privé"** pour le retirer de la galerie

### 3.6 Gérer les accessoires (équipement)

Disponible uniquement pour les personnages **approuvés** :
1. Cliquer sur **"Équipement"** sur la carte du personnage
2. Les articles sont organisés par onglets : **Vêtements**, **Armures**, **Accessoires**, **Armes**
3. Cocher/décocher les articles souhaités
4. Cliquer sur **"Sauvegarder"**

### 3.7 Modifier un personnage

Disponible uniquement pour les personnages **approuvés** :
1. Cliquer sur **"Modifier"** sur la carte du personnage
2. Modifier les caractéristiques d'apparence
3. Si le personnage est public : un message d'avertissement indique qu'il repassera en validation
4. Valider → le personnage repasse en statut "En attente"

### 3.8 Dupliquer un personnage

1. Cliquer sur **"Dupliquer"** sur la carte du personnage
2. Saisir un **nouveau nom** (obligatoire)
3. La copie est créée avec les mêmes accessoires, en statut "En attente de validation"

### 3.9 Supprimer un personnage

1. Cliquer sur **"Supprimer"** sur la carte du personnage
2. Confirmer dans la fenêtre de confirmation
3. Le personnage et ses données associées sont définitivement supprimés

### 3.10 Noter et commenter un personnage

Sur la page publique d'un personnage (accessible depuis la galerie) :
1. Sélectionner une note de **1 à 5 étoiles** (clic interactif)
2. Saisir un commentaire
3. Valider → l'avis est soumis à modération employé
4. Une fois approuvé, l'avis est visible publiquement

> Un utilisateur ne peut poster qu'**un seul avis** par personnage. Le propriétaire ne peut pas noter son propre personnage.

### 3.11 Contacter l'équipe

1. Cliquer sur **"Contact"** dans la navigation
2. Email et pseudo sont **pré-remplis et verrouillés** si connecté
3. Saisir le message (10 à 1000 caractères)
4. Envoyer → confirmation de réception affichée

### 3.12 Supprimer son compte (RGPD)

1. Dans l'espace utilisateur, cliquer sur **"Supprimer mon compte"**
2. Confirmer dans la fenêtre de sécurité
3. Toutes les données personnelles sont effacées (personnages, avis en cascade)

---

## 4. Parcours Employé

### 4.1 Connexion

URL : `/connexion` → redirection automatique vers `/espace-employe`

> Si le compte est suspendu par l'administrateur, la connexion est refusée avec un message explicite.

### 4.2 Modération des personnages

Dans l'onglet **"Personnages à modérer"** :

1. La liste affiche tous les personnages en attente de validation (`autorise IS NULL`), qu'ils soient publics ou privés
2. Cliquer sur **"Voir le détail"** pour consulter le portrait et les caractéristiques
3. **Approuver** : cliquer sur "Approuver" → le personnage est validé, le propriétaire reçoit un **email de confirmation**
4. **Rejeter** : cliquer sur "Rejeter" → saisir obligatoirement un **motif de rejet** → confirmer → le personnage est définitivement supprimé, le propriétaire reçoit un **email avec le motif**

> Un employé **ne peut pas modérer ses propres personnages** (protection anti-conflit d'intérêt).

### 4.3 Modération des avis

Dans l'onglet **"Avis à modérer"** :

1. Liste des commentaires en attente (`statut = 'en_attente'`)
2. **Approuver** : l'avis est publié, le propriétaire du personnage reçoit un **email de notification**
3. **Refuser** : l'avis est rejeté (non supprimé, conservé avec statut `refuse`)
4. **Supprimer** : suppression définitive de l'avis

### 4.4 Gestion des articles (équipements)

Dans l'onglet **"Articles"** :

| Action | Description |
|---|---|
| **Créer** | Ajouter un nouvel article (nom, type : vêtement/armure/accessoire/arme, image) |
| **Modifier** | Changer le nom, le type ou l'image |
| **Désactiver** | Masquer l'article des listes sans le supprimer |
| **Supprimer** | Supprimer définitivement (impossible si l'article est utilisé par des personnages) |

### 4.5 Gestion des utilisateurs

Dans l'onglet **"Utilisateurs"** :

| Action | Description |
|---|---|
| **Suspendre** | Bloquer la connexion d'un utilisateur |
| **Réactiver** | Lever la suspension |
| **Supprimer** | Supprimer le compte et toutes les données en cascade |

> Protections : impossible de suspendre/supprimer un **administrateur** ou son **propre compte**.

---

## 5. Parcours Administrateur

### 5.1 Connexion

URL : `/connexion` → redirection automatique vers `/espace-admin`

### 5.2 Gestion des employés

Dans l'onglet **"Employés"** :

| Action | Description |
|---|---|
| **Créer un employé** | Formulaire : pseudo, email, mot de passe (CNIL 12+ caractères avec indicateur de force) |
| **Modifier** | Changer le pseudo, l'email ou le mot de passe |
| **Suspendre / Activer** | Bloquer ou débloquer la connexion de l'employé |
| **Supprimer** | Supprimer le compte employé |

### 5.3 Consultation des logs MongoDB

Dans l'onglet **"Logs MongoDB"** :

**Filtres disponibles :**
- **Pseudo** : filtrer par pseudo utilisateur (recherche partielle)
- **ID Personnage** : filtrer par identifiant numérique
- **Action** : sélectionner dans la liste (création, modification, partage, équipement, etc.)
- **Date début / Date fin** : période de recherche

**Actions tracées :**

| Code action | Signification |
|---|---|
| `creation` | Création d'un personnage |
| `modification_apparence` | Modification des caractéristiques visuelles |
| `ajout_equipement` | Ajout d'un article à un personnage |
| `suppression_equipement` | Retrait d'un article |
| `partage` | Personnage rendu public |
| `arret_partage` | Personnage rendu privé |
| `suppression` | Suppression d'un personnage |
| `duplication` | Duplication d'un personnage |
| `moderation_approbation` | Approbation par un employé |
| `moderation_rejet` | Rejet par un employé |
| `employe_creation` | Création d'un compte employé |
| `employe_suppression` | Suppression d'un compte employé |
| `utilisateur_suppression` | Suppression d'un compte utilisateur |

La pagination affiche **50 logs par page**.

---

## 6. Pages publiques

| Page | URL | Description |
|---|---|---|
| Accueil | `/` | Présentation de PixelVerse Studios et FantasyRealm Online |
| Galerie | `/personnages` | Personnages publics approuvés avec filtres |
| Détail personnage | `/personnage/{id}` | Portrait, caractéristiques, avis |
| Contact | `/contact` | Formulaire de contact |
| Mentions légales | `/mentions-legales` | Informations légales |
| CGV | `/conditions-generales-de-vente` | Conditions générales |
| Politique de confidentialité | `/politique-de-confidentialite` | RGPD |
