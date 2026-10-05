<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\SalesOrderRepositoryInterface;

final class SalesOrderService
{
    public function __construct(private SalesOrderRepositoryInterface $repository)
    {
    }

    public function create(array $data, array $details, int $createdBy): int
    {
        if ($details === []) {
            throw new \DomainException('Minimal satu detail produk harus diisi.');
        }
        if ((int) ($data['customer_id'] ?? 0) < 1 || (int) ($data['warehouse_id'] ?? 0) < 1 || $createdBy < 1) {
            throw new \DomainException('Customer, gudang, dan Sales wajib dipilih.');
        }
        $this->validateOrderDate((string) ($data['so_date'] ?? ''));
        foreach ($details as $detail) {
            if (!is_array($detail)
                || (int) ($detail['product_id'] ?? 0) < 1
                || !is_numeric($detail['qty'] ?? null)
                || (float) $detail['qty'] <= 0
                || !is_numeric($detail['price'] ?? null)
                || (float) $detail['price'] < 0) {
                throw new \DomainException('Produk, jumlah, dan harga pada setiap detail SO harus valid.');
            }
        }
        return $this->repository->create($data, $details, $createdBy);
    }

    public function transition(int $id, string $target, int $actorId, string $role): void
    {
        $this->repository->transition($id, $target, $actorId, $role);
    }

    private function validateOrderDate(string $date): void
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            throw new \DomainException('Tanggal SO tidak valid.');
        }
    }
}
