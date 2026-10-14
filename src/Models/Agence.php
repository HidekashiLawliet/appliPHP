<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Company sites between which trips are proposed (table `agences`).
 */
final class Agence extends Model
{
    protected function table(): string
    {
        return 'agences';
    }

    protected function defaultOrder(): string
    {
        return 'nom';
    }

    /**
     * Returns the agency with this name, ignoring the one with $exceptId.
     *
     * @param int|null $exceptId Agency to ignore (the one being renamed).
     * @return array<string, mixed>|null
     */
    public function findByNom(string $nom, ?int $exceptId = null): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM agences WHERE LOWER(nom) = LOWER(:nom) AND id <> :except_id',
            ['nom' => $nom, 'except_id' => $exceptId ?? 0]
        );
    }

    /**
     * Returns the ids of all agencies.
     *
     * @return list<int>
     */
    public function ids(): array
    {
        return array_map(static fn (array $row): int => (int) $row['id'], $this->all());
    }

    /**
     * Creates an agency and returns its id.
     */
    public function create(string $nom): int
    {
        return $this->insert(['nom' => $nom]);
    }

    /**
     * Renames an agency. Trips keep pointing to it through their foreign keys.
     */
    public function rename(int $id, string $nom): void
    {
        $this->updateById($id, ['nom' => $nom]);
    }

    /**
     * Tells whether a trip starts from or goes to this agency.
     */
    public function isUsed(int $id): bool
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS total FROM trajets WHERE agence_depart_id = :depart OR agence_arrivee_id = :arrivee',
            ['depart' => $id, 'arrivee' => $id]
        );

        return $row !== null && (int) $row['total'] > 0;
    }
}
