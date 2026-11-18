# Charte graphique — FantasyRealm Online

**Projet :** FantasyRealm Online — Application de gestion de personnages MMORPG\
**Client :** PixelVerse Studios\
**Version :** 1.2 — 18 novembre 2026 (couleurs du site alignées sur la charte, contrastes RGAA, maquettes exportées)

---

## 1. Palette de couleurs

Les couleurs de la charte sont des nuances de la palette Tailwind CSS :
nuance 500 pour la couleur de base, nuance 600 pour le survol (par exemple
`#8b5cf6` → `#7c3aed` pour le violet). Elles sont appliquées à Bootstrap dans
`assets/styles/app.css`, en remplaçant ses variables CSS (`--bs-*-rgb` pour
les classes `text-*`, `bg-*` et `border-*`, `--bs-btn-*` pour les boutons).

### Couleurs principales

| Nom | Code hex | Aperçu | Usage |
|---|---|---|---|
| Violet principal | `#8b5cf6` | ██ | Logo, liens actifs, icônes, badges, accents |
| Violet des boutons | `#7c3aed` | ██ | Fond des boutons primaires à texte blanc |
| Violet survol | `#6d28d9` | ██ | Survol des boutons primaires |
| Ambre / Or | `#f59e0b` | ██ | Accent secondaire, étoiles de notation, dégradé |

Les boutons primaires utilisent `#7c3aed` plutôt que `#8b5cf6` : le texte
blanc n'atteint que 4,2:1 sur `#8b5cf6`, sous le seuil de 4,5:1 du RGAA, et
5,7:1 sur `#7c3aed` (voir la section 1.6).

### Dégradés

```
Dégradé signature : linear-gradient(135deg, #8b5cf6 → #f59e0b)
```
Utilisé sur le titre principal de l'accueil (« Légende ») et les éléments de mise en valeur.

```
Boutons « hero » : linear-gradient(135deg, #7c3aed → #6d28d9)
```
Utilisé sur les grands boutons d'appel à l'action de l'accueil (« Créer un compte », « Rejoindre la communauté »).

### Couleurs de fond (thème dark)

| Nom | Code hex | Usage |
|---|---|---|
| Fond global | `#1a1a2e` | Départ du dégradé de fond du `<body>` |
| Fond secondaire | `#111827` | Barre de navigation (à 90 % d'opacité) |
| Fond cards | `#1e1e2e` | Cartes (`.card.bg-dark`, `.card-fantasy`), modales |
| Fond sombre profond | `#0f0f23` | Pied de page, fin du dégradé de fond |

Fond de page : `linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f0f23 100%)`.
Les sections alternées de l'accueil utilisent le fond des cartes en
transparence (`rgba(30, 30, 46, 0.5)` et `0.3`).

### Couleurs de texte

| Nom | Code hex | Usage |
|---|---|---|
| Texte principal | `#e2e8f0` | Corps de texte |
| Texte secondaire | `#9ca3af` | Sous-titres, métadonnées, paragraphes secondaires (classe `text-secondary`) |
| Texte désactivé | `#6c757d` | Éléments inactifs, bordures des cartes |
| Texte sur couleur vive | `#0f0f23` | Texte des badges et boutons pleins verts, rouges et jaunes |

### Couleurs sémantiques (Bootstrap surchargées)

| Sémantique | Code hex | Survol | Usage |
|---|---|---|---|
| Succès | `#22c55e` | `#16a34a` | Approbations, badge « Approuvé », bouton « Partager » |
| Danger | `#ef4444` | `#dc2626` | Suppressions, rejets, badges « Rejeté » et « Suspendu » |
| Avertissement | `#eab308` | `#ca8a04` | Alertes, badge « En attente », bouton « Dupliquer » |
| Info | `#0dcaf0` | — | Badge « Partagé », bouton « Équipement » |

Sur les badges et boutons pleins verts, rouges et jaunes, le texte est foncé
(`#0f0f23`) : le texte blanc n'y atteindrait que 2,3:1 sur le vert et 3,8:1
sur le rouge. Exception : au survol du bouton rouge (`#dc2626`), le texte
redevient blanc (4,8:1).

La couleur info reste le cyan de Bootstrap (`#0dcaf0`). Le bleu `#3b82f6`
prévu initialement ferait tomber le badge « Partagé » à environ 3,2:1.

### 1.6 Contrastes mesurés (RGAA, critère 3.2)

Mesures faites le 17 novembre 2026 sur le site, sur le fond des cartes
`#1e1e2e` sauf mention contraire. Seuil exigé pour un texte de taille
normale : 4,5:1.

| Combinaison | Contraste | Conforme |
|---|---|---|
| Texte secondaire `#9ca3af` sur le fond | 6,5:1 | Oui |
| Texte blanc sur bouton `#7c3aed` | 5,7:1 | Oui |
| Texte blanc sur survol `#6d28d9` | 7,1:1 | Oui |
| Texte `#0f0f23` sur vert `#22c55e` | 8,3:1 | Oui |
| Texte foncé sur jaune `#eab308` | 8,6:1 | Oui |
| Texte `#0f0f23` sur rouge `#ef4444` | 5,0:1 | Oui |
| Texte vert `#22c55e` | 7,2:1 | Oui |
| Texte jaune `#eab308` | 8,6:1 | Oui |
| Texte cyan `#0dcaf0` | 8,4:1 | Oui |
| Texte violet `#8b5cf6` (bouton « Modifier », lien actif) | 3,9:1 | **Non** |
| Texte rouge `#ef4444` (bouton « Supprimer ») | 4,4:1 | **Non** |
| Texte blanc sur badge de genre violet `#8b5cf6` (galerie) | 4,2:1 | **Non** |

Les trois combinaisons non conformes restent à corriger.

---

## 2. Typographie

### Police principale

**Pile de polices système de Bootstrap 5.3** — aucune Google Font externe chargée.

```css
font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue",
             "Noto Sans", "Liberation Sans", Arial, sans-serif;
```

Avantages : chargement instantané (0 requête réseau), cohérence système, lisibilité native sur tous les OS.

### Police décorative (personnages SVG)

**Georgia, serif** — utilisée exclusivement pour les noms de personnages dans les portraits SVG générés côté serveur (`SvgPersonnageRenderer.php`).

### Hiérarchie typographique

| Niveau | Balise | Taille | Poids | Usage |
|---|---|---|---|---|
| Titre accueil | `h1 .display-3` | 4.5rem | 700 | Titre principal de l'accueil |
| Titre page | `h1 .display-5` / `.display-6` | 3rem / 2.5rem | 700 | Titres principaux des pages |
| Titre section | `h2 .h4` | 1.5rem | 500 | En-têtes de sections |
| Titre carte | `h2 .h6` | 1rem | 500 | Titres de cards personnages |
| Corps | `p` | 1rem | 400 | Texte courant |
| Petit texte | `small` | 0.875rem | 400 | Métadonnées, dates, libellés |

Les tailles `display-*` sont celles des grands écrans : Bootstrap les réduit sur mobile.

---

## 3. Iconographie

**Bibliothèque :** Bootstrap Icons 1.11.3 (CDN)

```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
```

Icônes principales utilisées :

| Icône | Classe | Contexte |
|---|---|---|
| Personnage | `bi-person-bounding-box` | Galerie, cartes |
| Épée / Bouclier | `bi-shield-lock` | Espace admin |
| Engrenage | `bi-gear` | Paramètres, actions |
| Étoile | `bi-star-fill` | Système de notation |
| Œil | `bi-eye` | Partage public |
| Verrou | `bi-lock` | Personnage privé |
| Crayon | `bi-pencil-square` | Modifier |
| Corbeille | `bi-trash` | Supprimer |
| Personnes | `bi-people-fill` | Liste employés |
| Journal | `bi-journal-text` | Logs MongoDB |

---

## 4. Composants UI

### Cartes personnages

- Background : `bg-dark border-secondary` (fond `#1e1e2e`, bordure `#6c757d`)
- Image : hauteur fixe 280px dans la galerie, 220px dans l'espace utilisateur, `object-fit: cover`
- Hover : translation Y -4px + ombre portée (transition 0.2s)
- Badge genre : `bg-primary bg-opacity-25` dans le template, affiché en violet plein `#8b5cf6` car la surcharge de `.bg-primary` dans `app.css` ignore l'opacité

### Boutons

| Variante | Classe Bootstrap | Couleur | Usage |
|---|---|---|---|
| Primaire | `btn-primary` | Fond `#7c3aed`, texte blanc | Actions principales (Filtrer, Créer un personnage) |
| Hero | `btn-hero` | Dégradé `#7c3aed` → `#6d28d9` | Appels à l'action de l'accueil |
| Hero contour | `btn-hero-outline` | Contour et texte `#8b5cf6` | Action secondaire de l'accueil |
| Contour violet | `btn-outline-primary` | `#8b5cf6` | Modifier |
| Contour gris | `btn-outline-secondary` | `#9ca3af` | Rendre privé, réinitialiser les filtres |
| Contour info | `btn-outline-info` | `#0dcaf0` | Équipement |
| Contour jaune | `btn-outline-warning` | `#eab308` | Dupliquer |
| Succès | `btn-success` / `btn-outline-success` | `#22c55e` | Approbations, Partager |
| Danger | `btn-danger` / `btn-outline-danger` | `#ef4444` | Suppressions |

### Formulaires

- Thème dark : inputs avec `bg-dark`, `border-secondary`, texte `#e2e8f0`
- Indicateur force mot de passe : barre de progression Bootstrap (`progress-bar`) avec couleurs sémantiques
- Validations côté client en temps réel (JavaScript vanilla)

---

## 5. Thème global

- **Mode :** Dark exclusif (`<html data-bs-theme="dark">`)
- **Ambiance :** Fantastique / médiéval futuriste
- **Framework CSS :** Bootstrap 5.3.3 (CDN)
- **Responsive :** Mobile-first, breakpoints Bootstrap standards (sm/md/lg/xl)
- **Accessibilité :** RGAA — `lang="fr"`, skip link, `aria-*`, labels associés, alt text, contrastes mesurés (section 1.6)

---

## 6. Maquettes (wireframes & mockups)

Les maquettes ont été réalisées **le 16 novembre 2026, après le développement**,
à partir de l'application en ligne, avec **Claude Design** (outil de conception
assisté par IA d'Anthropic). Elles n'ont donc pas servi à concevoir le site :
elles le documentent.

Le même jour, elles ont été comparées aux templates Twig et au site en ligne,
puis corrigées pour lui correspondre. Le 17 novembre, après l'alignement du
site sur la charte, les mockups ont repris les nouvelles couleurs.

12 écrans : 3 pages, chacune en desktop (1440 px) et en mobile (390 px), en
wireframe et en mockup.

| Page | Desktop (1440 px) | Mobile (390 px) |
|---|---|---|
| Accueil (`/`) | [wireframe](maquettes/wireframe-accueil-desktop.png) · [mockup](maquettes/mockup-accueil-desktop.png) | [wireframe](maquettes/wireframe-accueil-mobile.png) · [mockup](maquettes/mockup-accueil-mobile.png) |
| Galerie des personnages (`/personnages`) | [wireframe](maquettes/wireframe-galerie-desktop.png) · [mockup](maquettes/mockup-galerie-desktop.png) | [wireframe](maquettes/wireframe-galerie-mobile.png) · [mockup](maquettes/mockup-galerie-mobile.png) |
| Espace utilisateur (`/espace-utilisateur`) | [wireframe](maquettes/wireframe-espace-utilisateur-desktop.png) · [mockup](maquettes/mockup-espace-utilisateur-desktop.png) | [wireframe](maquettes/wireframe-espace-utilisateur-mobile.png) · [mockup](maquettes/mockup-espace-utilisateur-mobile.png) |

- **Wireframes :** niveaux de gris, blocs et barres à la place des paragraphes.
- **Mockups :** couleurs du site, vrais textes, portraits générés par
  l'application, données du jeu d'essai (compte `chevalier_noir`, personnages
  Thorin, Elyndra, Zephyr et Gareth, ce dernier en attente de validation).

Les 12 écrans ont été exportés du canevas en images PNG le 18 novembre 2026,
dans [`docs/maquettes/`](maquettes/), à leur taille réelle. La version PDF de la
charte (`docs/charte-graphique.pdf`) les reproduit en annexe.
