<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Contract\SalesOrderRepositoryInterface;

final class InMemorySalesOrderRepository implements SalesOrderRepositoryInterface
{
    public array $created = [];
    public array $transitions = [];

    public function create(array $data, array $details, int $createdBy): int
    {
        $this->created = compact('data', 'details', 'createdBy');
        return 42;
    }

    public function transition(int $id, string $target, int $actorId, string $role): void
    {
        $this->transitions[] = compact('id', 'target', 'actorId', 'role');
    }
}
