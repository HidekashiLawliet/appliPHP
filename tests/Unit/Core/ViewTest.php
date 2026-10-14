<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\View;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(View::class)]
final class ViewTest extends TestCase
{
    private View $view;

    protected function setUp(): void
    {
        $this->view = new View(dirname(__DIR__, 3) . '/src/Views');
    }

    public function testEscapesHtml(): void
    {
        self::assertSame('&lt;b&gt;&quot;x&quot; &amp; &#039;y&#039;&lt;/b&gt;', $this->view->e('<b>"x" & \'y\'</b>'));
        self::assertSame('', $this->view->e(null));
    }

    public function testFormatsSqlDates(): void
    {
        self::assertSame('02/01/2030 08:05', $this->view->date('2030-01-02 08:05:00'));
        self::assertSame('2030-01-02T08:05', $this->view->date('2030-01-02 08:05:00', 'Y-m-d\TH:i'));
        self::assertSame('', $this->view->date(null));
    }

    public function testRendersATemplateInsideTheLayout(): void
    {
        $html = $this->view->render('errors/error', [
            'status' => 404,
            'message' => '<oops>',
            'currentUser' => null,
            'flashes' => [],
            'currentPath' => '',
        ]);

        self::assertStringContainsString('<!DOCTYPE html>', $html);
        self::assertStringContainsString('404', $html);
        self::assertStringContainsString('&lt;oops&gt;', $html);
    }

    public function testRejectsPathTraversal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->view->render('../../.env');
    }
}
