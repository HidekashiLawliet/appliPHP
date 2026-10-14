<?php

declare(strict_types=1);

namespace App\Validators;

/**
 * Validates the name of an agency.
 */
final class AgenceValidator
{
    public const MAX_LENGTH = 100;

    /**
     * Returns the list of errors for this name (empty when valid).
     *
     * Uniqueness is checked by the controller, which has access to the model.
     *
     * @return list<string>
     */
    public function validate(string $nom): array
    {
        $nom = trim($nom);

        if ($nom === '') {
            return ["Le nom de l'agence est obligatoire."];
        }
        if (mb_strlen($nom) > self::MAX_LENGTH) {
            return [sprintf("Le nom de l'agence ne doit pas dépasser %d caractères.", self::MAX_LENGTH)];
        }
        if (preg_match("/^[\\p{L}\\p{N} '\\-]+$/u", $nom) !== 1) {
            return ["Le nom de l'agence contient des caractères non autorisés."];
        }

        return [];
    }
}
