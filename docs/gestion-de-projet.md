# Documentation de gestion de projet — FantasyRealm Online

**Projet :** FantasyRealm Online — Application de gestion de personnages MMORPG\
**Client :** PixelVerse Studios\
**Version :** 2.0 — 18 novembre 2026 (réécrite d'après le tableau Trello et le journal de bord)

---

## 1. Organisation

Projet réalisé seul, en parallèle de la préparation du baccalauréat.

### Outils de suivi

- **Tableau Trello** (depuis le 12 novembre 2026) :
  https://trello.com/b/y8GYJcye/fantasyrealm-ecf-d%C3%A9veloppeur-web-et-web-mobile
  - quatre listes : « À faire avant le rendu », « En cours », « Terminé »,
    « Après le rendu (préparation de l'oral) » ;
  - un préfixe entre crochets indique le domaine de chaque carte : Cadrage,
    Base de données, Docker, Git, Sécurité, Déploiement, Documentation,
    Copie, Oral.
- **Avant le tableau**, et toujours en complément, trois fichiers personnels
  hors du dépôt : un plan avec l'état d'avancement, un **journal de bord
  daté** (décisions, problèmes, sources, mesures de sécurité) et une liste des
  questions reportées. Les cartes « Terminé » du tableau reprennent ce journal.

### Méthode

- **Kanban léger** : une colonne par état, une carte par tâche.
- **Tâches courtes** (10 à 30 minutes), un seul bloc de travail à la fois.
- **Priorités revues** quand un jalon est manqué (voir la section 3).

---

## 2. Workflow Git

- `main` : version stable, celle qui est déployée.
- `dev` : branche de développement.
- **Une branche par modification**, nommée selon son type : `fix/…`,
  `feature/…`, `docs/…` (par exemple `fix/page-employe-personnage`,
  `docs/manuel-pdf`).
- Chaque branche est **testée en local**, puis fusionnée dans `dev` avec
  `git merge --no-ff` (le commit de fusion garde la trace de la branche).
  `dev` est ensuite fusionnée dans `main` avant chaque déploiement.

---

## 3. Chronologie

| Date | Étape |
|---|---|
| Février 2026 | Première version de l'application (Symfony, MySQL, MongoDB, Docker), réalisée avec l'aide de l'IA, et premier déploiement sur un serveur DigitalOcean. |
| 15 septembre | Lecture du sujet et cadrage : contraintes, livrables, questions à trancher. |
| 5 octobre | Jalon « squelette déployé et authentification » : **non atteint**. |
| 30 octobre | Décision de **reprendre le projet de février** plutôt que de repartir de zéro, à condition de comprendre, expliquer et savoir modifier chaque partie. Périmètre réduit en conséquence. |
| 8 novembre | Jalon « application en ligne » : **non atteint**. Priorités revues le 9 novembre. |
| 9 novembre | Remise d'aplomb : branche `dev`, mail sur 255 caractères et en minuscules, procédure de lancement du README, `APP_SECRET` retiré du `.env` versionné, certificat HTTPS renouvelé et renouvellement automatisé. |
| 12 novembre | Tableau Trello. MySQL relancé en production (arrêté depuis avril) et mémoire d'appoint ajoutée. Déploiement de la nouvelle version : logs MongoDB fonctionnels en production. Mots de passe des comptes de démonstration changés. |
| 14 novembre | Configuration de production : erreurs PHP journalisées au lieu d'être affichées, `.env.prod` exclu de l'image, mails envoyés par Brevo. Notation par étoiles corrigée. Déploiement. |
| 16 novembre | Maquettes (wireframes et mockups) réalisées avec Claude Design d'après l'application, nettoyage des comptes de production. |
| 17 novembre | Couleurs du site alignées sur la charte (contrastes RGAA), charte mise à jour, documentation versionnée, déploiement. |
| 18 novembre | Audit du code ; correction et déploiement de deux bugs (page employé de détail, « mot de passe oublié ») ; veille sur les vulnérabilités (`composer audit`) ; manuel et charte en PDF, maquettes exportées ; README corrigé ; dépôt prêt à être rendu public. |
| 19 novembre | Rendu de l'ECF. |

---

## 4. État des fonctionnalités

| Espace | Fonctionnalités | État |
|---|---|---|
| Public | Accueil, menu, pied de page (mentions légales, CGV, confidentialité), contact avec vérification du pseudo, galerie avec filtres (genre, dates, pseudo), page détail avec accessoires et avis validés | En ligne |
| Utilisateur | Inscription, connexion avec redirection selon le rôle, mot de passe oublié (mot de passe temporaire à changer), création, modification, accessoires, partage, duplication, suppression, avis | En ligne |
| Employé | Modération des personnages (mail au propriétaire, motif de rejet obligatoire) et des avis, catalogue d'articles, suppression de personnages, suspension et suppression de comptes | En ligne |
| Administrateur | Comptes employés (création, modification, suspension, suppression), consultation des logs MongoDB | En ligne |

Le 30 octobre, j'avais prévu de réduire le périmètre (filtres, duplication,
catalogue). Le code repris contenait déjà ces fonctionnalités : elles sont
restées en place.

---

## 5. Difficultés rencontrées et solutions

| Problème | Cause | Solution |
|---|---|---|
| Pages des personnages en erreur 500 en production | Serveur à 1 Go de RAM sans mémoire d'appoint : Linux avait arrêté MySQL faute de mémoire en avril | Sauvegarde, 2 Go de mémoire d'appoint (swap), redémarrage de MySQL |
| Certificat HTTPS expiré | Renouvellement automatique cassé : Certbot a besoin du port 80, occupé par Nginx | Trois scripts appelés par Certbot : arrêt de Nginx, copie du certificat, redémarrage |
| Logs MongoDB impossibles en production | Extension PHP MongoDB compilée sans SSL | `libssl-dev` ajouté dans le `Dockerfile`, image reconstruite |
| Mails non envoyés en production | DigitalOcean bloque les ports d'envoi 587 et 465 | Brevo sur le port 2525 |
| Erreurs PHP affichées aux visiteurs | Pas de `php.ini` dans l'image | `php.ini-production` installé dans l'image : erreurs journalisées, jamais affichées |
| Déploiement cassé par des fichiers copiés depuis Windows | Fins de ligne Windows (CRLF) dans les scripts | Conversion en fins de ligne Unix avant l'envoi |
| Site inchangé après une mise à jour | Cache Symfony conservé dans le volume `var/` | `cache:clear` après chaque déploiement |
| Push vers GitHub refusé | Commits signés avec mon adresse mail personnelle, protégée par GitHub | Adresse remplacée par l'adresse « noreply » de GitHub dans tout l'historique |

---

## 6. Déploiements

Chaque mise en production suit la même procédure :

1. sauvegarde de la base MySQL ;
2. image PHP en service conservée sous une étiquette, pour revenir en arrière ;
3. construction de la nouvelle image pendant que le site reste en ligne ;
4. bascule, avec une coupure de 12 à 16 secondes, puis contrôle automatique
   des pages ; en cas d'échec, retour automatique à l'image conservée.

Mises en production : 12, 14, 17 et 18 novembre 2026.

---

## 7. Après le rendu (préparation de l'oral)

- Comprendre le code parcours par parcours et savoir l'expliquer.
- Tester les parcours complets de chaque rôle.
- Mettre à jour les dépendances (`composer audit` du 18 novembre : 61 avis de
  sécurité, correctifs disponibles en Symfony 8.1).
- Corriger les points relevés par l'audit du code : validation du nom
  contournable après approbation, image du personnage reçue du navigateur sans
  contrôle, erreurs 500 sur certaines saisies, conservation des logs (RGPD).
- Sécuriser l'accès SSH au serveur (connexion `root` par mot de passe à fermer).
