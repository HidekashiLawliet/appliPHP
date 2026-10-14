-- -----------------------------------------------------------------------------
-- Touche pas au klaxon - creation de la base de donnees
--
-- A executer en premier, avec un compte qui a le droit CREATE DATABASE :
--   mysql -u root -p < database/00_create_database.sql
--
-- ATTENTION : supprime la base "tpak" si elle existe deja.
-- (`composer db:install` fait la meme chose avec le nom DB_NAME du fichier .env.)
-- -----------------------------------------------------------------------------

DROP DATABASE IF EXISTS tpak;

CREATE DATABASE tpak
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
