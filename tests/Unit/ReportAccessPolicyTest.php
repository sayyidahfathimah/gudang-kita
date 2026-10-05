<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Service\ReportAccessPolicy;
use PHPUnit\Framework\TestCase;

final class ReportAccessPolicyTest extends TestCase
{
    public function testSalesCanOnlyDownloadOrderReport(): void
    {
        $policy = new ReportAccessPolicy();
        self::assertSame(['orders'], $policy->allowedReports('Sales'));
        self::assertTrue($policy->canDownload('Sales', 'orders'));
        self::assertFalse($policy->canDownload('Sales', 'stock'));
        self::assertFalse($policy->canDownload('Sales', 'movements'));
    }

    public function testWarehouseStaffCannotDownloadOrderReport(): void
    {
        $policy = new ReportAccessPolicy();
        self::assertTrue($policy->canDownload('WarehouseStaff', 'stock'));
        self::assertTrue($policy->canDownload('WarehouseStaff', 'movements'));
        self::assertFalse($policy->canDownload('WarehouseStaff', 'orders'));
    }

    public function testUnknownRoleHasNoReportAccess(): void
    {
        $policy = new ReportAccessPolicy();
        self::assertSame([], $policy->allowedReports('Member'));
        self::assertFalse($policy->canDownload('Member', 'orders'));
    }
}
