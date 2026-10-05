<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Contract\PurchaseOrderWorkflowRepositoryInterface;

final class InMemoryPurchaseOrderRepository implements PurchaseOrderWorkflowRepositoryInterface
{
    public array $created = [];
    public array $transitions = [];
    public array $receipts = [];
    public ?array $order = null;

    public function create(array $data, array $details): int
    {
        $this->created = compact('data', 'details');
        return 24;
    }

    public function transition(int $id, string $target, string $role): void
    {
        $this->transitions[] = compact('id', 'target', 'role');
    }

    public function receive(int $id, array $receivedQuantities, int $actorId): void
    {
        $this->receipts[] = compact('id', 'receivedQuantities', 'actorId');
    }

    public function find(int $id): ?array
    {
        return $this->order;
    }
}
