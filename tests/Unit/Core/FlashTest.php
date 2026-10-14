<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Flash;
use App\Core\Session;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Flash::class)]
#[UsesClass(Session::class)]
final class FlashTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testMessagesAreStoredInTheSessionUntilPulled(): void
    {
        Flash::add(Flash::SUCCESS, 'Trajet cree.');
        Flash::add(Flash::ERROR, 'Oups.');

        // Simulates the redirection: only the session survives.
        self::assertArrayHasKey('_flash', $_SESSION);
        self::assertSame([
            ['type' => 'success', 'message' => 'Trajet cree.'],
            ['type' => 'danger', 'message' => 'Oups.'],
        ], Flash::pull());
    }

    public function testPullEmptiesTheQueue(): void
    {
        Flash::add(Flash::INFO, 'Une fois.');
        Flash::pull();

        self::assertSame([], Flash::pull());
        self::assertArrayNotHasKey('_flash', $_SESSION);
    }

    public function testCorruptedSessionDataIsIgnored(): void
    {
        $_SESSION['_flash'] = ['nope', ['type' => 'success']];

        self::assertSame([], Flash::peek());
    }
}
