<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\TrajetValidator;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TrajetValidator::class)]
final class TrajetValidatorTest extends TestCase
{
    private TrajetValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new TrajetValidator([1, 2, 3], new DateTimeImmutable('2030-01-01 12:00:00'));
    }

    /**
     * @return array<string, string>
     */
    private static function validInput(): array
    {
        return [
            'agence_depart_id' => '1',
            'agence_arrivee_id' => '2',
            'date_depart' => '2030-01-02T08:00',
            'date_arrivee' => '2030-01-02T10:30',
            'places_total' => '4',
            'places_disponibles' => '3',
        ];
    }

    public function testValidInputIsConvertedForTheDatabase(): void
    {
        self::assertTrue($this->validator->validate(self::validInput()));
        self::assertSame([], $this->validator->errors());
        self::assertSame([
            'agence_depart_id' => 1,
            'agence_arrivee_id' => 2,
            'date_depart' => '2030-01-02 08:00:00',
            'date_arrivee' => '2030-01-02 10:30:00',
            'places_total' => 4,
            'places_disponibles' => 3,
        ], $this->validator->data());
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function invalidInputs(): iterable
    {
        yield 'agence manquante' => [['agence_depart_id' => ''], 'obligatoires'];
        yield 'agence inconnue' => [['agence_arrivee_id' => '99'], "n'existe pas"];
        yield 'agence non numerique' => [['agence_depart_id' => '1 OR 1=1'], 'obligatoires'];
        yield 'memes agences' => [['agence_arrivee_id' => '1'], 'différente'];
        yield 'date invalide' => [['date_depart' => '2030-02-31T08:00'], 'valides'];
        yield 'depart dans le passe' => [['date_depart' => '2029-12-31T08:00'], 'passé'];
        yield 'arrivee avant depart' => [['date_arrivee' => '2030-01-02T07:00'], 'postérieure'];
        yield 'zero place' => [['places_total' => '0'], 'entre 1 et'];
        yield 'trop de places' => [['places_total' => '10'], 'entre 1 et'];
        yield 'places dispo > total' => [['places_disponibles' => '5'], 'places disponibles'];
        yield 'places dispo negatives' => [['places_disponibles' => '-1'], 'places disponibles'];
    }

    /**
     * @param array<string, string> $override
     */
    #[DataProvider('invalidInputs')]
    public function testInvalidInputIsRejected(array $override, string $expectedError): void
    {
        self::assertFalse($this->validator->validate($override + self::validInput()));
        self::assertStringContainsString($expectedError, implode(' ', $this->validator->errors()));
    }

    public function testDataCannotBeReadBeforeASuccessfulValidation(): void
    {
        $this->expectException(\LogicException::class);
        $this->validator->data();
    }
}
