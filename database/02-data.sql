-- FantasyRealm Online - Données initiales
-- Fichier: 02-data.sql

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

USE fantasyrealm_db;

-- Insertion des rôles
INSERT INTO role (libelle) VALUES ('utilisateur');
INSERT INTO role (libelle) VALUES ('employe');
INSERT INTO role (libelle) VALUES ('administrateur');

-- Compte administrateur (mot de passe: Admin@12345!)
INSERT INTO utilisateur (pseudo, mail, password, suspendu, motdepasse_a_modifier, id_role)
VALUES ('admin', 'admin@pixelverse.com', '$2y$12$JZaeAc479q4w5Hxi/DE16usYD7PK3DqHXxm6EcIaTrpKxQ8xpGana', FALSE, FALSE, 3);

-- Compte employé de test (mot de passe: Employe@1234!)
INSERT INTO utilisateur (pseudo, mail, password, suspendu, motdepasse_a_modifier, id_role)
VALUES ('moderateur1', 'moderateur@pixelverse.com', '$2y$12$YUcWxcvOwMNQtkviSmwpAeep5ybqRmQK70cmWMzfqobhf7ImTeW9a', FALSE, FALSE, 2);

-- Comptes utilisateurs de test (mot de passe: User@12345678!)
INSERT INTO utilisateur (pseudo, mail, password, suspendu, motdepasse_a_modifier, id_role)
VALUES ('chevalier_noir', 'chevalier@test.com', '$2y$12$0QUW3DEXp1YzUJIIkLw61ObDEIIFKEpo4/zGELE2TriLliIhkVGtO', FALSE, FALSE, 1);

INSERT INTO utilisateur (pseudo, mail, password, suspendu, motdepasse_a_modifier, id_role)
VALUES ('elfe_sylvain', 'elfe@test.com', '$2y$12$0QUW3DEXp1YzUJIIkLw61ObDEIIFKEpo4/zGELE2TriLliIhkVGtO', FALSE, FALSE, 1);

INSERT INTO utilisateur (pseudo, mail, password, suspendu, motdepasse_a_modifier, id_role)
VALUES ('mage_arcane', 'mage@test.com', '$2y$12$0QUW3DEXp1YzUJIIkLw61ObDEIIFKEpo4/zGELE2TriLliIhkVGtO', FALSE, FALSE, 1);

-- Personnages de test
INSERT INTO personnage (nom, genre, couleur_peau, couleur_yeux, couleur_cheveux, forme_yeux, forme_nez, forme_bouche, partage, autorise, date_creation, id_utilisateur)
VALUES ('Thorin Forgehache', 'Masculin', 'bronze', 'marron', 'brun', 'ronds', 'large', 'moyenne', TRUE, TRUE, NOW(), 3);

INSERT INTO personnage (nom, genre, couleur_peau, couleur_yeux, couleur_cheveux, forme_yeux, forme_nez, forme_bouche, partage, autorise, date_creation, id_utilisateur)
VALUES ('Elyndra Lumenarbre', 'Féminin', 'pale', 'vert', 'blond-platine', 'amande', 'fin', 'fine', TRUE, TRUE, NOW(), 4);

INSERT INTO personnage (nom, genre, couleur_peau, couleur_yeux, couleur_cheveux, forme_yeux, forme_nez, forme_bouche, partage, autorise, date_creation, id_utilisateur)
VALUES ('Zephyr Ombrevent', 'Autre', 'olive', 'violet', 'noir', 'amande-allongee', 'aquilin', 'fine', TRUE, TRUE, NOW(), 5);

INSERT INTO personnage (nom, genre, couleur_peau, couleur_yeux, couleur_cheveux, forme_yeux, forme_nez, forme_bouche, partage, autorise, date_creation, id_utilisateur)
VALUES ('Gareth Bouclier', 'Masculin', 'claire', 'bleu', 'brun-fonce', 'ronds', 'droit', 'large', FALSE, NULL, NOW(), 3);

-- Articles de personnalisation
-- Vêtements
INSERT INTO article (nom, actif, type) VALUES ('Tunique du Voyageur', TRUE, 'vetement');
INSERT INTO article (nom, actif, type) VALUES ('Robe de Mage Ancienne', TRUE, 'vetement');
INSERT INTO article (nom, actif, type) VALUES ('Cape du Rôdeur', TRUE, 'vetement');

-- Armures
INSERT INTO article (nom, actif, type) VALUES ('Plastron en Mithril', TRUE, 'armure');
INSERT INTO article (nom, actif, type) VALUES ('Cotte de Mailles Elfique', TRUE, 'armure');
INSERT INTO article (nom, actif, type) VALUES ('Armure de Plates du Dragon', TRUE, 'armure');

-- Accessoires
INSERT INTO article (nom, actif, type) VALUES ('Amulette de Protection', TRUE, 'accessoire');
INSERT INTO article (nom, actif, type) VALUES ('Couronne des Anciens', TRUE, 'accessoire');
INSERT INTO article (nom, actif, type) VALUES ('Anneau de Puissance', TRUE, 'accessoire');

-- Armes
INSERT INTO article (nom, actif, type) VALUES ('Épée Flamboyante', TRUE, 'arme');
INSERT INTO article (nom, actif, type) VALUES ('Arc de la Forêt Éternelle', TRUE, 'arme');
INSERT INTO article (nom, actif, type) VALUES ('Bâton du Sage', TRUE, 'arme');

-- Équipements assignés aux personnages de test
INSERT INTO personnage_article (id_personnage, id_article) VALUES (1, 4);  -- Thorin + Plastron en Mithril
INSERT INTO personnage_article (id_personnage, id_article) VALUES (1, 10); -- Thorin + Épée Flamboyante
INSERT INTO personnage_article (id_personnage, id_article) VALUES (2, 2);  -- Elyndra + Robe de Mage
INSERT INTO personnage_article (id_personnage, id_article) VALUES (2, 11); -- Elyndra + Arc de la Forêt
INSERT INTO personnage_article (id_personnage, id_article) VALUES (2, 7);  -- Elyndra + Amulette
INSERT INTO personnage_article (id_personnage, id_article) VALUES (3, 3);  -- Zephyr + Cape du Rôdeur
INSERT INTO personnage_article (id_personnage, id_article) VALUES (3, 12); -- Zephyr + Bâton du Sage

-- Commentaires de test
INSERT INTO commentaire (note, commentaire, date_commentaire, statut, id_personnage, id_utilisateur)
VALUES (5, 'Superbe personnage ! L''apparence est vraiment travaillée, bravo pour les détails.', CURDATE(), 'approuve', 1, 4);

INSERT INTO commentaire (note, commentaire, date_commentaire, statut, id_personnage, id_utilisateur)
VALUES (4, 'Belle création, j''adore le style elfique. Le choix des couleurs est harmonieux.', CURDATE(), 'approuve', 2, 3);

INSERT INTO commentaire (note, commentaire, date_commentaire, statut, id_personnage, id_utilisateur)
VALUES (3, 'Original mais pourrait être amélioré avec plus d''accessoires.', CURDATE(), 'en_attente', 3, 3);
