-- -----------------------------------------------------------------------------
-- Touche pas au klaxon - schema physique (MySQL 8 / MariaDB 10.4+)
--
--   mysql -u root -p tpak < database/01_schema.sql
--
-- Le script est rejouable : il supprime puis recree les tables.
-- MCD / MLD : voir docs/database.md
-- -----------------------------------------------------------------------------

SET NAMES utf8mb4;

-- Les tables filles d'abord, a cause des cles etrangeres.
DROP TABLE IF EXISTS trajets;
DROP TABLE IF EXISTS agences;
DROP TABLE IF EXISTS utilisateurs;

-- Entite UTILISATEUR : employes, fournis par le service RH.
CREATE TABLE utilisateurs (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nom          VARCHAR(50)   NOT NULL,
    prenom       VARCHAR(50)   NOT NULL,
    telephone    VARCHAR(20)   NOT NULL,
    email        VARCHAR(150)  NOT NULL,
    mot_de_passe VARCHAR(255)  NOT NULL COMMENT 'Hash password_hash() (bcrypt/argon2), jamais en clair',
    est_admin    TINYINT(1)    NOT NULL DEFAULT 0,
    CONSTRAINT pk_utilisateurs PRIMARY KEY (id),
    CONSTRAINT uq_utilisateurs_email UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Entite AGENCE : sites de l'entreprise.
CREATE TABLE agences (
    id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    CONSTRAINT pk_agences PRIMARY KEY (id),
    CONSTRAINT uq_agences_nom UNIQUE (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Entite TRAJET. Les associations PROPOSER, PARTIR_DE et ARRIVER_A
-- (cardinalites 1,1 cote TRAJET) deviennent trois cles etrangeres.
CREATE TABLE trajets (
    id                 INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    utilisateur_id     INT UNSIGNED     NOT NULL COMMENT 'PROPOSER : auteur du trajet',
    agence_depart_id   INT UNSIGNED     NOT NULL COMMENT 'PARTIR_DE',
    agence_arrivee_id  INT UNSIGNED     NOT NULL COMMENT 'ARRIVER_A',
    date_depart        DATETIME         NOT NULL,
    date_arrivee       DATETIME         NOT NULL,
    places_total       TINYINT UNSIGNED NOT NULL,
    places_disponibles TINYINT UNSIGNED NOT NULL,
    cree_le            DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_trajets PRIMARY KEY (id),
    CONSTRAINT fk_trajets_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateurs (id) ON DELETE CASCADE,
    CONSTRAINT fk_trajets_agence_depart FOREIGN KEY (agence_depart_id)
        REFERENCES agences (id) ON DELETE RESTRICT,
    CONSTRAINT fk_trajets_agence_arrivee FOREIGN KEY (agence_arrivee_id)
        REFERENCES agences (id) ON DELETE RESTRICT,
    CONSTRAINT chk_trajets_agences_differentes CHECK (agence_depart_id <> agence_arrivee_id),
    CONSTRAINT chk_trajets_dates CHECK (date_arrivee > date_depart),
    CONSTRAINT chk_trajets_places CHECK (places_total >= 1 AND places_disponibles <= places_total),
    INDEX idx_trajets_date_depart (date_depart)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
