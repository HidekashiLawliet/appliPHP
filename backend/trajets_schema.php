<?php
/**
 * Database schema helpers for trip records.
 */

function ensureTrajetsTable(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS trajets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_email VARCHAR(150) NOT NULL COLLATE utf8mb4_general_ci,
        agence_depart VARCHAR(100) NOT NULL COLLATE utf8mb4_general_ci,
        date_depart DATETIME NOT NULL,
        agence_arrivee VARCHAR(100) NOT NULL COLLATE utf8mb4_general_ci,
        date_arrivee DATETIME NOT NULL,
        places_total INT NOT NULL,
        places_disponibles INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_trajets_depart (date_depart),
        INDEX idx_trajets_user (user_email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

    $pdo->exec('ALTER TABLE trajets CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
}
