<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controller\PurchaseOrderController;
use App\Controller\SalesOrderController;
use App\Repository\PurchaseOrderRepository;
use App\Repository\SalesOrderRepository;
use App\Support\ControllerFactory;
use PHPUnit\Framework\TestCase;

final class ControllerFactoryTest extends TestCase
{
    public function testPurchaseControllerAndServiceShareInjectedRepository(): void
    {
        $pdo = (new \ReflectionClass(\PDO::class))->newInstanceWithoutConstructor();
        $controller = ControllerFactory::create(PurchaseOrderController::class, $pdo);
        self::assertInstanceOf(PurchaseOrderController::class, $controller);
        $repository = (new \ReflectionProperty($controller, 'repo'))->getValue($controller);
        $service = (new \ReflectionProperty($controller, 'service'))->getValue($controller);
        self::assertInstanceOf(PurchaseOrderRepository::class, $repository);
        self::assertSame($repository, (new \ReflectionProperty($service, 'repository'))->getValue($service));
    }

    public function testSalesControllerAndServiceShareInjectedRepository(): void
    {
        $pdo = (new \ReflectionClass(\PDO::class))->newInstanceWithoutConstructor();
        $controller = ControllerFactory::create(SalesOrderController::class, $pdo);
        self::assertInstanceOf(SalesOrderController::class, $controller);
        $repository = (new \ReflectionProperty($controller, 'repo'))->getValue($controller);
        $service = (new \ReflectionProperty($controller, 'service'))->getValue($controller);
        self::assertInstanceOf(SalesOrderRepository::class, $repository);
        self::assertSame($repository, (new \ReflectionProperty($service, 'repository'))->getValue($service));
    }
}
