<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\AgenceValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(AgenceValidator::class)]
final class AgenceValidatorTest extends TestCase
{
    #[TestWith(['Paris'])]
    #[TestWith(['Saint-Étienne'])]
    #[TestWith(["L'Isle-d'Abeau"])]
    public function testValidNamesAreAccepted(string $nom): void
    {
        self::assertSame([], (new AgenceValidator())->validate($nom));
    }

    #[TestWith(['', 'obligatoire'])]
    #[TestWith(['   ', 'obligatoire'])]
    #[TestWith(['<script>', 'non autorisés'])]
    public function testInvalidNamesAreRejected(string $nom, string $expectedError): void
    {
        $errors = (new AgenceValidator())->validate($nom);

        self::assertCount(1, $errors);
        self::assertStringContainsString($expectedError, $errors[0]);
    }

    public function testTooLongNameIsRejected(): void
    {
        $errors = (new AgenceValidator())->validate(str_repeat('a', AgenceValidator::MAX_LENGTH + 1));

        self::assertStringContainsString('dépasser', $errors[0] ?? '');
    }
}
