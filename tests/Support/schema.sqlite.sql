-- SQLite mirror of database/01_schema.sql, used by the in-memory test database.
-- Same tables, keys and constraints; only the types differ.

CREATE TABLE utilisateurs (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    nom          TEXT    NOT NULL,
    prenom       TEXT    NOT NULL,
    telephone    TEXT    NOT NULL,
    email        TEXT    NOT NULL UNIQUE,
    mot_de_passe TEXT    NOT NULL,
    est_admin    INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE agences (
    id  INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT    NOT NULL UNIQUE
);

CREATE TABLE trajets (
    id                 INTEGER PRIMARY KEY AUTOINCREMENT,
    utilisateur_id     INTEGER NOT NULL REFERENCES utilisateurs (id) ON DELETE CASCADE,
    agence_depart_id   INTEGER NOT NULL REFERENCES agences (id) ON DELETE RESTRICT,
    agence_arrivee_id  INTEGER NOT NULL REFERENCES agences (id) ON DELETE RESTRICT,
    date_depart        TEXT    NOT NULL,
    date_arrivee       TEXT    NOT NULL,
    places_total       INTEGER NOT NULL,
    places_disponibles INTEGER NOT NULL,
    cree_le            TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (agence_depart_id <> agence_arrivee_id),
    CHECK (date_arrivee > date_depart),
    CHECK (places_total >= 1 AND places_disponibles <= places_total)
);
