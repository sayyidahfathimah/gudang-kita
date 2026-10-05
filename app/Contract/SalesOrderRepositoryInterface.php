<?php

declare(strict_types=1);

namespace App\Contract;

interface SalesOrderRepositoryInterface
{
    public function create(array $data, array $details, int $createdBy): int;
    public function transition(int $id, string $target, int $actorId, string $role): void;
}
