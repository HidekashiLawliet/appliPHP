<?php

declare(strict_types=1);

namespace App\Validators;

use App\Models\Trajet;
use DateTimeImmutable;

/**
 * Validates the trip form and converts it into data ready for the model.
 *
 * @phpstan-import-type TrajetData from Trajet
 */
final class TrajetValidator
{
    public const MAX_PLACES = 9;

    /** @var list<string> */
    private array $errors = [];

    /** @var TrajetData|null */
    private ?array $data = null;

    /**
     * @param list<int>         $agenceIds Ids of the existing agencies.
     * @param DateTimeImmutable $now       Reference time for "not in the past".
     */
    public function __construct(private readonly array $agenceIds, private readonly DateTimeImmutable $now)
    {
    }

    /**
     * Validates raw form input. Returns true when the input is valid.
     *
     * @param array<string, string> $input Raw values from the form.
     */
    public function validate(array $input): bool
    {
        $this->errors = [];
        $this->data = null;

        $departId = self::toInt($input['agence_depart_id'] ?? '');
        $arriveeId = self::toInt($input['agence_arrivee_id'] ?? '');
        $depart = self::toDate($input['date_depart'] ?? '');
        $arrivee = self::toDate($input['date_arrivee'] ?? '');
        $placesTotal = self::toInt($input['places_total'] ?? '');
        $placesDisponibles = self::toInt($input['places_disponibles'] ?? '');

        if ($departId === null || $arriveeId === null) {
            $this->errors[] = "Les agences de départ et d'arrivée sont obligatoires.";
        } elseif (!in_array($departId, $this->agenceIds, true) || !in_array($arriveeId, $this->agenceIds, true)) {
            $this->errors[] = "L'agence choisie n'existe pas.";
        } elseif ($departId === $arriveeId) {
            $this->errors[] = "L'agence d'arrivée doit être différente de l'agence de départ.";
        }

        if ($depart === null || $arrivee === null) {
            $this->errors[] = "Les dates de départ et d'arrivée sont obligatoires et doivent être valides.";
        } else {
            if ($depart < $this->now) {
                $this->errors[] = 'La date de départ ne peut pas être dans le passé.';
            }
            if ($arrivee <= $depart) {
                $this->errors[] = "La date d'arrivée doit être postérieure à la date de départ.";
            }
        }

        if ($placesTotal === null || $placesTotal < 1 || $placesTotal > self::MAX_PLACES) {
            $this->errors[] = sprintf('Le nombre total de places doit être compris entre 1 et %d.', self::MAX_PLACES);
        } elseif ($placesDisponibles === null || $placesDisponibles < 0 || $placesDisponibles > $placesTotal) {
            $this->errors[] = 'Les places disponibles doivent être comprises entre 0 et le nombre total de places.';
        }

        if ($this->errors !== []
            || $departId === null || $arriveeId === null || $depart === null || $arrivee === null
            || $placesTotal === null || $placesDisponibles === null
        ) {
            return false;
        }

        $this->data = [
            'agence_depart_id' => $departId,
            'agence_arrivee_id' => $arriveeId,
            'date_depart' => $depart->format('Y-m-d H:i:s'),
            'date_arrivee' => $arrivee->format('Y-m-d H:i:s'),
            'places_total' => $placesTotal,
            'places_disponibles' => $placesDisponibles,
        ];

        return true;
    }

    /**
     * Returns the error messages of the last validation.
     *
     * @return list<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Returns the validated data.
     *
     * @return TrajetData
     * @throws \LogicException When called before a successful validate().
     */
    public function data(): array
    {
        if ($this->data === null) {
            throw new \LogicException('Aucune donnee validee : appelez validate() avant data().');
        }

        return $this->data;
    }

    /**
     * Parses a strictly integer string, or returns null.
     */
    private static function toInt(string $value): ?int
    {
        $int = filter_var(trim($value), FILTER_VALIDATE_INT);

        return $int === false ? null : $int;
    }

    /**
     * Parses a date sent by a datetime-local input (or a SQL datetime).
     */
    private static function toDate(string $value): ?DateTimeImmutable
    {
        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s', 'Y-m-d H:i'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, trim($value));
            if ($date !== false && $date->format($format) === trim($value)) {
                return $date;
            }
        }

        return null;
    }
}
