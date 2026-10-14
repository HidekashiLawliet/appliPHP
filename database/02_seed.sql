-- -----------------------------------------------------------------------------
-- Touche pas au klaxon - jeu d'essai
--
--   mysql -u root -p tpak < database/02_seed.sql
--
-- Source : data/jeu-d-essais/agences.txt et data/jeu-d-essais/users.txt
-- Mot de passe de tous les employes : Password123!  (stocke hache, bcrypt)
-- Les dates des trajets sont relatives a CURDATE() pour etre toujours a venir.
-- -----------------------------------------------------------------------------

SET NAMES utf8mb4;

DELETE FROM trajets;
DELETE FROM agences;
DELETE FROM utilisateurs;

-- Agences
INSERT INTO agences (nom) VALUES
    ('Paris'),
    ('Lyon'),
    ('Marseille'),
    ('Toulouse'),
    ('Nice'),
    ('Nantes'),
    ('Strasbourg'),
    ('Montpellier'),
    ('Bordeaux'),
    ('Lille'),
    ('Rennes'),
    ('Reims');

-- Employes
INSERT INTO utilisateurs (nom, prenom, telephone, email, mot_de_passe, est_admin) VALUES
    ('Martin', 'Alexandre', '0612345678', 'alexandre.martin@email.fr', '$2y$12$Z.gwkWZ.wL.QqqZ5Jdo.OOMgTnWMEB/1gsBlxXYWZt9YX8L8d5PV6', 0),
    ('Dubois', 'Sophie', '0698765432', 'sophie.dubois@email.fr', '$2y$12$H5NkkT.Qo2DsvS9SRRxSU.O6pFVcB7b9qoGfFYX3qH5IkEYqQZkDm', 0),
    ('Bernard', 'Julien', '0622446688', 'julien.bernard@email.fr', '$2y$12$9G7aS8z3KoSY9Ek.dt2vGO49vXs3yuvWSkIVqS.BLXhO0zfbqx0A.', 0),
    ('Moreau', 'Camille', '0611223344', 'camille.moreau@email.fr', '$2y$12$PBksSHDPLdA5FpgrwD7J..rv2EaETbGQKx.gGSQMFI9tYnuY7tejO', 0),
    ('Lefèvre', 'Lucie', '0777889900', 'lucie.lefevre@email.fr', '$2y$12$3buuKErkMZsFd9p0iCQWc.5T56siHq1Py8dwwt8aJryth5/0s4l2u', 0),
    ('Leroy', 'Thomas', '0655443322', 'thomas.leroy@email.fr', '$2y$12$SUVtnE26tDGACCk7Sxml5eIFYT.tJPMQ7R25fnwr3/g4950nxf6ou', 0),
    ('Roux', 'Chloé', '0633221199', 'chloe.roux@email.fr', '$2y$12$Zf9kY4w.IekQAU7WLkF8wOdmMa3VcQvhGjlT6YLfEdDs5oZnlUtD.', 0),
    ('Petit', 'Maxime', '0766778899', 'maxime.petit@email.fr', '$2y$12$qGsC3qCfOPLbTkXIA2.FNOEDtGWpfr8mfa/rN8KCZmaV0RYo2hnly', 0),
    ('Garnier', 'Laura', '0688776655', 'laura.garnier@email.fr', '$2y$12$gIXTAmYpOjJ1KMMzEdPt..tOHsfavkQX30K3mP62pbNPkgqh2g.Jm', 0),
    ('Dupuis', 'Antoine', '0744556677', 'antoine.dupuis@email.fr', '$2y$12$hu5KJQ8uwtz0jnQmsLNh8.lBXQI1gIyTp.RuH5SpIwqrOMmnQpMl6', 0),
    ('Lefebvre', 'Emma', '0699887766', 'emma.lefebvre@email.fr', '$2y$12$38y1Zf4CB7RMszrank94ruJgJTyQCW0qYS.3YeSviJWxJcjFI4Tqm', 0),
    ('Fontaine', 'Louis', '0655667788', 'louis.fontaine@email.fr', '$2y$12$7qWdd2/TWtoH7C7z6nXNLuo5pZhtOPEZFORZfp1itulfyPmSafUYW', 0),
    ('Chevalier', 'Clara', '0788990011', 'clara.chevalier@email.fr', '$2y$12$2l4KvuHKWO7EFdpcVyyN1uLfsUSVCm23gegymxLwa0f.GjXnO3hcS', 0),
    ('Robin', 'Nicolas', '0644332211', 'nicolas.robin@email.fr', '$2y$12$QZH6m9pHZ4GVty2ltbD2MusUy6SXY8pBRIfFJyWXbGsik4ggheCPG', 0),
    ('Gauthier', 'Marine', '0677889922', 'marine.gauthier@email.fr', '$2y$12$sF9DltrPef74/iRWkHTF3.H4hUuRxHJhK7LORKUKa0nwV1eAAwFYu', 0),
    ('Fournier', 'Pierre', '0722334455', 'pierre.fournier@email.fr', '$2y$12$UTk.6h4fzxQ8zO4gk3xVe.IuE9SlolPnPPsvHbT3T/CZhC9VkIH.2', 0),
    ('Girard', 'Sarah', '0688665544', 'sarah.girard@email.fr', '$2y$12$7nl7amwimPiS.DAcPyvSB.5riRDuPGYwmQ8ouTvOr/EKFeuqaBiYa', 0),
    ('Lambert', 'Hugo', '0611223366', 'hugo.lambert@email.fr', '$2y$12$1lZiyIjNi8xs/YLE6jLpWeQo2Wm6ufVOxDEfsUGvXdx3UN/C4qpUG', 0),
    ('Masson', 'Julie', '0733445566', 'julie.masson@email.fr', '$2y$12$1JOZUqhZOPlMqyVjZC1hwuYIjjLzVQfC/4MoWh.chnCE1DsczGqpK', 0),
    ('Henry', 'Arthur', '0666554433', 'arthur.henry@email.fr', '$2y$12$NLKWhxV675xRfm8TF3niJeaxOFv2MCQYE6P8C2PQw.Q/z36wmgGgO', 0);

-- Trajets
INSERT INTO trajets (utilisateur_id, agence_depart_id, agence_arrivee_id, date_depart, date_arrivee, places_total, places_disponibles) VALUES
    ((SELECT id FROM utilisateurs WHERE email = 'alexandre.martin@email.fr'),
     (SELECT id FROM agences WHERE nom = 'Paris'),
     (SELECT id FROM agences WHERE nom = 'Lyon'),
     DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 2 DAY), INTERVAL 8 HOUR),
     DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 2 DAY), INTERVAL 12 HOUR),
     4, 3),
    ((SELECT id FROM utilisateurs WHERE email = 'sophie.dubois@email.fr'),
     (SELECT id FROM agences WHERE nom = 'Lyon'),
     (SELECT id FROM agences WHERE nom = 'Marseille'),
     DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 3 DAY), INTERVAL 7 HOUR),
     DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 3 DAY), INTERVAL 10 HOUR),
     3, 2),
    ((SELECT id FROM utilisateurs WHERE email = 'julien.bernard@email.fr'),
     (SELECT id FROM agences WHERE nom = 'Toulouse'),
     (SELECT id FROM agences WHERE nom = 'Bordeaux'),
     DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 5 DAY), INTERVAL 9 HOUR),
     DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 5 DAY), INTERVAL 11 HOUR),
     4, 4),
    ((SELECT id FROM utilisateurs WHERE email = 'camille.moreau@email.fr'),
     (SELECT id FROM agences WHERE nom = 'Nantes'),
     (SELECT id FROM agences WHERE nom = 'Rennes'),
     DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 7 DAY), INTERVAL 17 HOUR),
     DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 7 DAY), INTERVAL 19 HOUR),
     2, 1),
    ((SELECT id FROM utilisateurs WHERE email = 'thomas.leroy@email.fr'),
     (SELECT id FROM agences WHERE nom = 'Lille'),
     (SELECT id FROM agences WHERE nom = 'Paris'),
     DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 10 DAY), INTERVAL 6 HOUR),
     DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 10 DAY), INTERVAL 9 HOUR),
     5, 0),
    ((SELECT id FROM utilisateurs WHERE email = 'emma.lefebvre@email.fr'),
     (SELECT id FROM agences WHERE nom = 'Strasbourg'),
     (SELECT id FROM agences WHERE nom = 'Reims'),
     DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 12 DAY), INTERVAL 8 HOUR),
     DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 12 DAY), INTERVAL 11 HOUR),
     3, 3);
