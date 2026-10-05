<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Service\PurchaseOrderService;
use DomainException;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryPurchaseOrderRepository;

final class PurchaseOrderServiceTest extends TestCase
{
    private function orderData(): array
    {
        return ['supplier_id' => 2, 'warehouse_id' => 1, 'po_date' => '2026-10-05'];
    }

    private function details(): array
    {
        return [['product_id' => 7, 'qty' => 5, 'price' => 1200]];
    }

    public function testCreatePassesValidOrderToInjectedRepository(): void
    {
        $repository = new InMemoryPurchaseOrderRepository();
        self::assertSame(24, (new PurchaseOrderService($repository))->create($this->orderData(), $this->details()));
        self::assertSame(7, $repository->created['details'][0]['product_id']);
    }

    public function testCreateRejectsMissingDetails(): void
    {
        $this->expectException(DomainException::class);
        (new PurchaseOrderService(new InMemoryPurchaseOrderRepository()))->create($this->orderData(), []);
    }

    public function testCreateRejectsInvalidDateAndNegativePrice(): void
    {
        $data = $this->orderData();
        $data['po_date'] = '2026-02-30';
        try {
            (new PurchaseOrderService(new InMemoryPurchaseOrderRepository()))->create($data, $this->details());
            self::fail('Tanggal kalender yang tidak valid harus ditolak.');
        } catch (DomainException) {
            self::assertTrue(true);
        }

        $invalidDetails = [['product_id' => 7, 'qty' => 5, 'price' => -1]];
        $this->expectException(DomainException::class);
        (new PurchaseOrderService(new InMemoryPurchaseOrderRepository()))->create($this->orderData(), $invalidDetails);
    }

    public function testReceiveRejectsDetailThatDoesNotBelongToOrder(): void
    {
        $repository = new InMemoryPurchaseOrderRepository();
        $repository->order = ['status' => 'Ordered', 'details' => [['id' => 5]]];
        $this->expectException(DomainException::class);
        (new PurchaseOrderService($repository))->receive(3, [99 => 1], 9);
    }

    public function testReceivePassesValidQuantityForOrderDetail(): void
    {
        $repository = new InMemoryPurchaseOrderRepository();
        $repository->order = ['status' => 'Ordered', 'details' => [['id' => 5]]];
        (new PurchaseOrderService($repository))->receive(3, [5 => 2], 9);
        self::assertSame([['id' => 3, 'receivedQuantities' => [5 => 2], 'actorId' => 9]], $repository->receipts);
    }
}
