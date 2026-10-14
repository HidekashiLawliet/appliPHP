-- -----------------------------------------------------------------------------
-- Touche pas au klaxon - compte administrateur
--
--   mysql -u root -p tpak < database/03_create_admin.sql
--
-- Identifiants : alexandre.martin@email.fr / Password123!
-- A CHANGER avant toute utilisation reelle : generer un nouveau hash avec
--   php -r "echo password_hash('NouveauMotDePasse', PASSWORD_DEFAULT);"
-- puis UPDATE utilisateurs SET mot_de_passe = '<hash>' WHERE email = 'alexandre.martin@email.fr';
-- Rejouable : si l'employe existe deja (jeu d'essai), il devient administrateur.
-- -----------------------------------------------------------------------------

SET NAMES utf8mb4;

INSERT INTO utilisateurs (nom, prenom, telephone, email, mot_de_passe, est_admin)
VALUES ('Martin', 'Alexandre', '0612345678', 'alexandre.martin@email.fr', '$2y$12$Y6bajWjwqNy9GO3ISnbPp.w3ATg44ZQTMJhgiQXMrFGu0v3DWerWq', 1)
ON DUPLICATE KEY UPDATE
    mot_de_passe = VALUES(mot_de_passe),
    est_admin = 1;
