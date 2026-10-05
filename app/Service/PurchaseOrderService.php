<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\PurchaseOrderWorkflowRepositoryInterface;

final class PurchaseOrderService
{
    public function __construct(private PurchaseOrderWorkflowRepositoryInterface $repository)
    {
    }

    public function create(array $data, array $details): int
    {
        if ($details === []) {
            throw new \DomainException('Minimal satu detail produk harus diisi.');
        }
        if ((int) ($data['supplier_id'] ?? 0) < 1 || (int) ($data['warehouse_id'] ?? 0) < 1) {
            throw new \DomainException('Supplier dan gudang wajib dipilih.');
        }
        $date = (string) ($data['po_date'] ?? '');
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            throw new \DomainException('Tanggal PO tidak valid.');
        }
        foreach ($details as $detail) {
            if (!is_array($detail)
                || (int) ($detail['product_id'] ?? 0) < 1
                || !is_numeric($detail['qty'] ?? null)
                || (float) $detail['qty'] <= 0
                || !is_numeric($detail['price'] ?? null)
                || (float) $detail['price'] < 0) {
                throw new \DomainException('Produk, jumlah, dan harga pada setiap detail PO harus valid.');
            }
        }
        return $this->repository->create($data, $details);
    }

    public function transition(int $id, string $target, string $role): void
    {
        $this->repository->transition($id, $target, $role);
    }

    public function receive(int $id, array $receivedQuantities, int $actorId): void
    {
        if ($actorId < 1) {
            throw new \DomainException('Petugas penerimaan tidak valid.');
        }
        $order = $this->repository->find($id);
        if (!$order || !in_array($order['status'], ['Ordered', 'PartiallyReceived'], true)) {
            throw new \DomainException('Hanya PO Ordered atau Partially Received yang dapat diterima.');
        }
        $validDetailIds = array_map(static fn (array $detail): int => (int) $detail['id'], $order['details'] ?? []);
        foreach ($receivedQuantities as $detailId => $quantity) {
            if (!ctype_digit((string) $detailId) || !in_array((int) $detailId, $validDetailIds, true)
                || !is_numeric($quantity) || (float) $quantity < 0) {
                throw new \DomainException('Jumlah penerimaan barang tidak valid.');
            }
        }
        $this->repository->receive($id, $receivedQuantities, $actorId);
    }
}
