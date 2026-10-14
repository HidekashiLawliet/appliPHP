<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Csrf;
use App\Core\Session;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Csrf::class)]
#[UsesClass(Session::class)]
final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testTokenIsGeneratedOnceAndReused(): void
    {
        $token = Csrf::token();

        self::assertSame(64, strlen($token));
        self::assertSame($token, Csrf::token());
    }

    public function testOnlyTheSessionTokenIsValid(): void
    {
        $token = Csrf::token();

        self::assertTrue(Csrf::isValid($token));
        self::assertFalse(Csrf::isValid('forged'));
        self::assertFalse(Csrf::isValid(''));
        self::assertFalse(Csrf::isValid(null));
    }

    public function testNothingIsValidWithoutASessionToken(): void
    {
        self::assertFalse(Csrf::isValid(''));
    }
}
