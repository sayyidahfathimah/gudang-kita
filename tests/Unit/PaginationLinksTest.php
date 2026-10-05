<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PaginationLinksTest extends TestCase
{
    public function testLinksKeepFiltersAndEscapeQueryString(): void
    {
        $html = paginationLinks(['page' => 2, 'pages' => 3], ['page' => 'masters', 'search' => 'a&b']);

        self::assertStringContainsString('search=a%26b&amp;current_page=1', $html);
        self::assertStringContainsString('class="page-btn current"', $html);
        self::assertStringContainsString('search=a%26b&amp;current_page=3', $html);
    }

    public function testSinglePageHasNoLinks(): void
    {
        self::assertSame('', paginationLinks(['page' => 1, 'pages' => 1], ['page' => 'masters']));
    }
}
