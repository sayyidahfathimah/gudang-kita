<?php

declare(strict_types=1);

namespace App\Support;

use App\Controller\PurchaseOrderController;
use App\Controller\SalesOrderController;
use App\Repository\InventoryRepository;
use App\Repository\PurchaseOrderRepository;
use App\Repository\SalesOrderRepository;
use App\Service\PurchaseOrderService;
use App\Service\SalesOrderService;

/** Manual composition root for the two order workflows. */
final class ControllerFactory
{
    public static function create(string $controllerClass, \PDO $pdo): object
    {
        if ($controllerClass === PurchaseOrderController::class) {
            $repository = new PurchaseOrderRepository($pdo);
            return new PurchaseOrderController(
                $pdo,
                $repository,
                new InventoryRepository($pdo),
                new PurchaseOrderService($repository)
            );
        }
        if ($controllerClass === SalesOrderController::class) {
            $repository = new SalesOrderRepository($pdo);
            return new SalesOrderController(
                $pdo,
                $repository,
                new InventoryRepository($pdo),
                new SalesOrderService($repository)
            );
        }
        return new $controllerClass($pdo);
    }
}
