<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ReportRepository;
use App\Exception\ReportOutputException;
use App\Security\Auth;
use App\Service\ReportAccessPolicy;
use DateTimeImmutable;

final class ReportController extends BaseController
{
    private ReportRepository $reports;
    private ReportAccessPolicy $access;

    public function __construct(\PDO $pdo, ?ReportRepository $reports = null, ?ReportAccessPolicy $access = null)
    {
        parent::__construct($pdo);
        $this->reports = $reports ?? new ReportRepository($pdo);
        $this->access = $access ?? new ReportAccessPolicy();
    }

    public function index(): void
    {
        Auth::require();
        $role = Auth::role();
        $allowed = $this->access->allowedReports($role);
        if ($allowed === []) {
            $this->forbidden();
        }

        $report = (string) ($_GET['report'] ?? $allowed[0]);
        if (!$this->access->canDownload($role, $report)) {
            $this->forbidden();
        }

        $filters = $this->dateFilters();
        if (($_GET['format'] ?? '') === 'csv') {
            $this->downloadCsv($report, $filters, $role);
            return;
        }

        $sales = [];
        if ($role !== 'WarehouseStaff') {
            $salesId = $role === 'Sales' ? Auth::id() : null;
            $sales = $this->reports->salesMonthly($filters['from'], $filters['to'], $salesId);
        }
        $this->view('reports/index', [
            'pageTitle' => 'Laporan',
            'role' => $role,
            'stock' => $role === 'Sales' ? [] : $this->reports->stockRows(),
            'purchase' => $role === 'Admin' ? $this->reports->purchaseMonthly($filters['from'], $filters['to']) : [],
            'sales' => $sales,
            'filters' => $filters,
            'report' => $report,
        ]);
    }

    private function dateFilters(): array
    {
        $today = new DateTimeImmutable('today');
        $fromInput = (string) ($_GET['from'] ?? $today->modify('first day of this month')->format('Y-m-d'));
        $toInput = (string) ($_GET['to'] ?? $today->format('Y-m-d'));
        $from = DateTimeImmutable::createFromFormat('!Y-m-d', $fromInput);
        $to = DateTimeImmutable::createFromFormat('!Y-m-d', $toInput);
        if (!$from || !$to || $from->format('Y-m-d') !== $fromInput || $to->format('Y-m-d') !== $toInput || $from > $to) {
            return ['from' => $today->modify('first day of this month')->format('Y-m-d'), 'to' => $today->format('Y-m-d')];
        }
        return ['from' => $fromInput, 'to' => $toInput];
    }

    private function downloadCsv(string $report, array $filters, string $role): void
    {
        if ($report === 'stock') {
            $rows = $this->reports->stockRows();
            $header = ['Kode Produk', 'Produk', 'Gudang', 'Stok Saat Ini', 'Stok Minimum'];
            $filename = 'laporan-stok.csv';
        } elseif ($report === 'orders') {
            $rows = $this->reports->orderRows($filters['from'], $filters['to'], $role === 'Sales' ? Auth::id() : null);
            $header = ['Jenis', 'Nomor Order', 'Tanggal', 'Status', 'Supplier/Customer', 'Total'];
            $filename = 'laporan-order.csv';
        } else {
            $rows = $this->reports->movementRows($filters['from'], $filters['to']);
            $header = ['Jenis Movement', 'Arah Penyesuaian', 'Nomor Referensi', 'Waktu', 'Kode Produk', 'Produk', 'Gudang', 'Qty', 'Catatan'];
            $filename = 'laporan-stock-ledger.csv';
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        $output = fopen('php://output', 'wb');
        if ($output === false) {
            throw new ReportOutputException('Laporan tidak dapat dibuat.');
        }
        fputcsv($output, $header);
        foreach ($rows as $row) {
            fputcsv($output, array_map(static fn (mixed $value): mixed => self::safeCsvCell($value), array_values($row)));
        }
        fclose($output);
    }

    private static function safeCsvCell(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value) === 1) {
            return "'".$value;
        }
        return $value;
    }

    private function forbidden(): never
    {
        http_response_code(403);
        require_once BASE_PATH.'/views/403.php';
        exit;
    }
}
