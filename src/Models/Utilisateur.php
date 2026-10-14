<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Employees allowed to use the intranet (table `utilisateurs`).
 *
 * Passwords are only ever stored as `password_hash()` hashes.
 */
final class Utilisateur extends Model
{
    protected function table(): string
    {
        return 'utilisateurs';
    }

    protected function defaultOrder(): string
    {
        return 'nom, prenom';
    }

    /**
     * Returns the user with this email address, or null.
     *
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne('SELECT * FROM utilisateurs WHERE email = :email', ['email' => $email]);
    }

    /**
     * Creates a user and returns its id. The password is hashed here.
     *
     * @param string $plainPassword Clear password, never stored as is.
     */
    public function create(
        string $nom,
        string $prenom,
        string $telephone,
        string $email,
        string $plainPassword,
        bool $estAdmin = false
    ): int {
        return $this->insert([
            'nom' => $nom,
            'prenom' => $prenom,
            'telephone' => $telephone,
            'email' => $email,
            'mot_de_passe' => password_hash($plainPassword, PASSWORD_DEFAULT),
            'est_admin' => $estAdmin ? 1 : 0,
        ]);
    }

    /**
     * Replaces the stored password hash (used to upgrade old hashes at login).
     */
    public function updatePasswordHash(int $id, string $hash): void
    {
        $this->updateById($id, ['mot_de_passe' => $hash]);
    }
}
