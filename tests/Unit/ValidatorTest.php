<?php

namespace Tests\Unit;

use App\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testRequiredReportsOnlyEmptyFields(): void
    {
        $errors = Validator::required(['name' => 'Produk', 'code' => ''], ['name', 'code', 'unit']);

        self::assertArrayNotHasKey('name', $errors);
        self::assertArrayHasKey('code', $errors);
        self::assertArrayHasKey('unit', $errors);
    }

    public function testEmailEnumAndDateOrderValidation(): void
    {
        self::assertTrue(Validator::email('user@example.test'));
        self::assertFalse(Validator::email('invalid-email'));
        self::assertTrue(Validator::enum('Admin', ['Admin', 'Member']));
        self::assertFalse(Validator::enum('Guest', ['Admin', 'Member']));
        self::assertTrue(Validator::dateOrder('2026-10-01', '2026-10-01'));
        self::assertFalse(Validator::dateOrder('2026-10-02', '2026-10-01'));
    }
}
