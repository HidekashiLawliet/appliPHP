<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use DateTimeInterface;

/**
 * Car-sharing trips between two agencies (table `trajets`).
 *
 * @phpstan-type TrajetData array{
 *     agence_depart_id: int,
 *     agence_arrivee_id: int,
 *     date_depart: string,
 *     date_arrivee: string,
 *     places_total: int,
 *     places_disponibles: int
 * }
 */
final class Trajet extends Model
{
    /**
     * Trip columns joined with agency names and author contact details.
     */
    private const SELECT_DETAILS = <<<'SQL'
        SELECT t.*,
               ad.nom AS agence_depart,
               aa.nom AS agence_arrivee,
               u.nom AS auteur_nom,
               u.prenom AS auteur_prenom,
               u.telephone AS auteur_telephone,
               u.email AS auteur_email
        FROM trajets t
        INNER JOIN agences ad ON ad.id = t.agence_depart_id
        INNER JOIN agences aa ON aa.id = t.agence_arrivee_id
        INNER JOIN utilisateurs u ON u.id = t.utilisateur_id
        SQL;

    protected function table(): string
    {
        return 'trajets';
    }

    protected function defaultOrder(): string
    {
        return 'date_depart';
    }

    /**
     * Returns future trips that still have free seats, soonest first.
     *
     * @return list<array<string, mixed>>
     */
    public function findUpcomingAvailable(DateTimeInterface $now): array
    {
        return $this->fetchAll(
            self::SELECT_DETAILS . ' WHERE t.places_disponibles > 0 AND t.date_depart >= :now ORDER BY t.date_depart',
            ['now' => $now->format('Y-m-d H:i:s')]
        );
    }

    /**
     * Returns every trip with its details (administration page).
     *
     * @return list<array<string, mixed>>
     */
    public function findAllWithDetails(): array
    {
        return $this->fetchAll(self::SELECT_DETAILS . ' ORDER BY t.date_depart DESC');
    }

    /**
     * Returns the trip only if it belongs to the given user.
     *
     * @return array<string, mixed>|null
     */
    public function findOwnedBy(int $id, int $utilisateurId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM trajets WHERE id = :id AND utilisateur_id = :utilisateur_id',
            ['id' => $id, 'utilisateur_id' => $utilisateurId]
        );
    }

    /**
     * Creates a trip for the given author and returns its id.
     *
     * @param TrajetData $data Validated data (see TrajetValidator).
     */
    public function create(int $utilisateurId, array $data): int
    {
        return $this->insert(['utilisateur_id' => $utilisateurId] + $data);
    }

    /**
     * Updates a trip with validated data.
     *
     * @param TrajetData $data Validated data (see TrajetValidator).
     */
    public function update(int $id, array $data): void
    {
        $this->updateById($id, $data);
    }

    /**
     * Deletes a trip only if it belongs to the given user.
     *
     * @return bool True when the trip was deleted.
     */
    public function deleteOwnedBy(int $id, int $utilisateurId): bool
    {
        return $this->execute(
            'DELETE FROM trajets WHERE id = :id AND utilisateur_id = :utilisateur_id',
            ['id' => $id, 'utilisateur_id' => $utilisateurId]
        ) > 0;
    }
}
