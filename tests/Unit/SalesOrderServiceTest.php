<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Service\SalesOrderService;
use DomainException;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemorySalesOrderRepository;

final class SalesOrderServiceTest extends TestCase
{
    private function validOrder(): array
    {
        return [
            'customer_id' => 2,
            'warehouse_id' => 1,
            'so_date' => '2026-10-05',
        ];
    }

    private function validDetails(): array
    {
        return [['product_id' => 1, 'qty' => 2, 'price' => 12500]];
    }

    public function testCreateSendsValidOrderToInjectedRepository(): void
    {
        $repository = new InMemorySalesOrderRepository();
        $service = new SalesOrderService($repository);

        self::assertSame(42, $service->create($this->validOrder(), $this->validDetails(), 7));
        self::assertSame(7, $repository->created['createdBy']);
        self::assertSame(1, $repository->created['details'][0]['product_id']);
    }

    public function testCreateRejectsEmptyDetails(): void
    {
        $this->expectException(DomainException::class);
        (new SalesOrderService(new InMemorySalesOrderRepository()))->create($this->validOrder(), [], 7);
    }

    public function testCreateRejectsInvalidOrderReferences(): void
    {
        $data = $this->validOrder();
        $data['warehouse_id'] = 0;
        $this->expectException(DomainException::class);
        (new SalesOrderService(new InMemorySalesOrderRepository()))->create($data, $this->validDetails(), 7);
    }

    public function testCreateRejectsInvalidDate(): void
    {
        $data = $this->validOrder();
        $data['so_date'] = '2026-02-30';
        $this->expectException(DomainException::class);
        (new SalesOrderService(new InMemorySalesOrderRepository()))->create($data, $this->validDetails(), 7);
    }

    public function testCreateRejectsZeroQuantityAndNegativePrice(): void
    {
        foreach ([['product_id' => 1, 'qty' => 0, 'price' => 10], ['product_id' => 1, 'qty' => 1, 'price' => -1]] as $detail) {
            try {
                (new SalesOrderService(new InMemorySalesOrderRepository()))->create($this->validOrder(), [$detail], 7);
                self::fail('Detail transaksi tidak valid harus ditolak.');
            } catch (DomainException) {
                self::assertTrue(true);
            }
        }
    }

    public function testTransitionUsesInjectedRepository(): void
    {
        $repository = new InMemorySalesOrderRepository();
        (new SalesOrderService($repository))->transition(11, 'Approved', 1, 'Admin');

        self::assertSame([['id' => 11, 'target' => 'Approved', 'actorId' => 1, 'role' => 'Admin']], $repository->transitions);
    }
}
