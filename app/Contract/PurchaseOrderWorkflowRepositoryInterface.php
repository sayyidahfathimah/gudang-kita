<?php

declare(strict_types=1);

namespace App\Contract;

interface PurchaseOrderWorkflowRepositoryInterface extends PurchaseOrderRepositoryInterface
{
    public function find(int $id): ?array;
}
