-- FantasyRealm Online - Schéma de base de données
-- Fichier: 01-schema.sql
-- Ce fichier crée la base de données et toutes les tables

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS fantasyrealm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fantasyrealm_db;

-- Table des rôles
CREATE TABLE IF NOT EXISTS role (
    id_role INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS utilisateur (
    id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
    pseudo VARCHAR(50) NOT NULL UNIQUE,
    mail VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    suspendu BOOLEAN DEFAULT FALSE NOT NULL,
    motdepasse_a_modifier BOOLEAN DEFAULT FALSE NOT NULL,
    id_role INT NOT NULL,
    CONSTRAINT fk_utilisateur_role FOREIGN KEY (id_role) REFERENCES role(id_role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des personnages
CREATE TABLE IF NOT EXISTS personnage (
    id_personnage INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL UNIQUE,
    genre VARCHAR(50) CHECK (genre IN ('Masculin', 'Féminin', 'Autre')),
    couleur_peau VARCHAR(50),
    couleur_yeux VARCHAR(50),
    couleur_cheveux VARCHAR(50),
    forme_yeux VARCHAR(50),
    forme_nez VARCHAR(50),
    forme_bouche VARCHAR(50),
    partage BOOLEAN DEFAULT FALSE NOT NULL,
    autorise BOOLEAN DEFAULT NULL,
    image LONGBLOB,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_utilisateur INT NOT NULL,
    CONSTRAINT fk_personnage_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index sur les colonnes fréquemment filtrées
CREATE INDEX idx_personnage_genre ON personnage(genre);
CREATE INDEX idx_personnage_partage ON personnage(partage);
CREATE INDEX idx_personnage_autorise ON personnage(autorise);

-- Table des commentaires
CREATE TABLE IF NOT EXISTS commentaire (
    id_commentaire INT AUTO_INCREMENT PRIMARY KEY,
    note INT NOT NULL CHECK (note BETWEEN 1 AND 5),
    commentaire VARCHAR(500) NOT NULL,
    date_commentaire DATE NOT NULL,
    statut VARCHAR(50) DEFAULT 'en_attente' NOT NULL CHECK (statut IN ('en_attente', 'approuve', 'refuse')),
    id_personnage INT NOT NULL,
    id_utilisateur INT NOT NULL,
    CONSTRAINT fk_commentaire_personnage FOREIGN KEY (id_personnage)
        REFERENCES personnage(id_personnage) ON DELETE CASCADE,
    CONSTRAINT fk_commentaire_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index sur le statut des commentaires
CREATE INDEX idx_commentaire_statut ON commentaire(statut);

-- Table des articles
CREATE TABLE IF NOT EXISTS article (
    id_article INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    actif BOOLEAN DEFAULT TRUE NOT NULL,
    image LONGBLOB,
    type VARCHAR(50) NOT NULL CHECK (type IN ('vetement', 'armure', 'accessoire', 'arme'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de liaison personnage_article (Many-to-Many)
CREATE TABLE IF NOT EXISTS personnage_article (
    id_personnage INT NOT NULL,
    id_article INT NOT NULL,
    PRIMARY KEY (id_personnage, id_article),
    CONSTRAINT fk_pa_personnage FOREIGN KEY (id_personnage)
        REFERENCES personnage(id_personnage) ON DELETE CASCADE,
    CONSTRAINT fk_pa_article FOREIGN KEY (id_article)
        REFERENCES article(id_article) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
