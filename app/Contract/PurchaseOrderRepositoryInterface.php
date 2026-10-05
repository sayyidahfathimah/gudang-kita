<?php

declare(strict_types=1);

namespace App\Contract;

interface PurchaseOrderRepositoryInterface
{
    public function create(array $data, array $details): int;
    public function transition(int $id, string $target, string $role): void;
    public function receive(int $id, array $receivedQuantities, int $actorId): void;
}
