<?php

declare(strict_types=1);

namespace App\Service;

final class ReportAccessPolicy
{
    public function allowedReports(string $role): array
    {
        return match ($role) {
            'Admin' => ['movements', 'orders', 'stock'],
            'Sales' => ['orders'],
            'WarehouseStaff' => ['movements', 'stock'],
            default => [],
        };
    }

    public function canDownload(string $role, string $report): bool
    {
        return in_array($report, $this->allowedReports($role), true);
    }
}
