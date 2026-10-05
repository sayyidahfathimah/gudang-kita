# Class Diagram As-Built — Gudang Kita

Diagram ini menggambarkan **kelas PHP yang benar-benar ada** pada aplikasi. Fokusnya adalah alur Purchase Order (PO), Sales Order (SO), dan laporan, karena alur tersebut mewakili pemisahan Controller, Service, dan Repository yang diminta brief. Diagram database dan relasi tabel berada di [ERD](../planning/erd.md).

## Alur transaksi PO dan SO

```mermaid
classDiagram
    direction LR

    class BaseController {
        #PDO pdo
    }
    class ControllerFactory {
        +create(controllerClass, PDO)
    }
    class PurchaseOrderController {
        -PurchaseOrderRepository repo
        -InventoryRepository inv
        -PurchaseOrderService service
        +store()
        +status(id)
        +receive(id)
    }
    class SalesOrderController {
        -SalesOrderRepository repo
        -InventoryRepository inventory
        -SalesOrderService service
        +store()
        +status(id)
    }
    class PurchaseOrderService {
        -PurchaseOrderWorkflowRepositoryInterface repository
        +create(data, details)
        +transition(id, target, role)
        +receive(id, quantities, actorId)
    }
    class SalesOrderService {
        -SalesOrderRepositoryInterface repository
        +create(data, details, createdBy)
        +transition(id, target, actorId, role)
    }
    class PurchaseOrderRepositoryInterface {
        <<interface>>
        +create(data, details)
        +transition(id, target, role)
        +receive(id, quantities, actorId)
    }
    class PurchaseOrderWorkflowRepositoryInterface {
        <<interface>>
        +find(id)
    }
    class SalesOrderRepositoryInterface {
        <<interface>>
        +create(data, details, createdBy)
        +transition(id, target, actorId, role)
    }
    class PurchaseOrderRepository {
        +create(data, details)
        +transition(id, target, role)
        +receive(id, quantities, actorId)
    }
    class SalesOrderRepository {
        +create(data, details, createdBy)
        +transition(id, target, actorId, role)
    }
    class InventoryRepository
    class InMemoryPurchaseOrderRepository
    class InMemorySalesOrderRepository
    class StockMovementType {
        <<enumeration>>
        Receipt
        Issue
        Adjustment
    }
    class PDO

    BaseController <|-- PurchaseOrderController
    BaseController <|-- SalesOrderController
    ControllerFactory --> PurchaseOrderController : inject dependency
    ControllerFactory --> SalesOrderController : inject dependency
    ControllerFactory --> PurchaseOrderRepository : instantiate
    ControllerFactory --> SalesOrderRepository : instantiate

    PurchaseOrderController --> PurchaseOrderService : menggunakan
    PurchaseOrderController --> PurchaseOrderRepository : membaca PO
    PurchaseOrderController --> InventoryRepository : pilihan form
    SalesOrderController --> SalesOrderService : menggunakan
    SalesOrderController --> SalesOrderRepository : membaca SO
    SalesOrderController --> InventoryRepository : pilihan form

    PurchaseOrderService ..> PurchaseOrderWorkflowRepositoryInterface : constructor injection
    SalesOrderService ..> SalesOrderRepositoryInterface : constructor injection
    PurchaseOrderRepositoryInterface <|-- PurchaseOrderWorkflowRepositoryInterface
    PurchaseOrderWorkflowRepositoryInterface <|.. PurchaseOrderRepository
    PurchaseOrderWorkflowRepositoryInterface <|.. InMemoryPurchaseOrderRepository
    SalesOrderRepositoryInterface <|.. SalesOrderRepository
    SalesOrderRepositoryInterface <|.. InMemorySalesOrderRepository

    PurchaseOrderRepository --> PDO : query dan transaksi
    SalesOrderRepository --> PDO : query dan transaksi
    InventoryRepository --> PDO : query katalog
    PurchaseOrderRepository ..> StockMovementType : Receipt
    SalesOrderRepository ..> StockMovementType : Issue
```

**Cara membaca panah:** `--|>` adalah pewarisan interface/kelas, `..|>` adalah implementasi interface, `-->` adalah pemakaian kelas konkret, dan `..>` adalah dependency pada kontrak atau enum. Dua kelas `InMemory...` ada di `tests/Fakes/`, bukan pada aplikasi produksi. `PurchaseOrderWorkflowRepositoryInterface` memperluas `PurchaseOrderRepositoryInterface` dengan operasi `find()`; **itulah tipe constructor** `PurchaseOrderService`. `SalesOrderService` menerima `SalesOrderRepositoryInterface`.

Saat menerima PO, `PurchaseOrderRepository` menulis `stocks` dan `stock_movements` bertipe `Receipt` dalam satu transaksi. Saat memenuhi SO, `SalesOrderRepository` mengunci baris stok (`FOR UPDATE`), menolak jumlah yang kurang, lalu menulis stok dan movement `Issue` dalam satu transaksi. `StockMovementType` adalah enum PHP; order, stok, dan ledger disimpan sebagai **tabel MySQL**, bukan kelas Entity PHP terpisah.

## Alur laporan

```mermaid
classDiagram
    direction LR
    class BaseController
    class ReportController {
        -ReportRepository reports
        -ReportAccessPolicy access
        +index()
    }
    class ReportAccessPolicy {
        +allowedReports(role)
        +canDownload(role, report)
    }
    class ReportRepository {
        +stockRows()
        +movementRows(from, to)
        +orderRows(from, to, actorId)
        +purchaseMonthly(from, to)
        +salesMonthly(from, to, salesId)
    }
    class PDO

    BaseController <|-- ReportController
    ReportController --> ReportAccessPolicy : cek izin
    ReportController --> ReportRepository : mengambil data
    ReportRepository --> PDO : query
```

`ControllerFactory` di [composition root](../../app/Support/ControllerFactory.php) membuat repository dan service PO/SO, lalu menginjeksi dependency wajib ke controller. Dependency inversion berlaku pada Service PO/SO melalui interface dengan implementasi MySQL dan fake untuk test. Controller CRUD dan laporan lain masih membuat repository konkret; hal ini dicatat sebagai [technical debt](../quality/tech-debt.md). Kode rujukan: [controller PO](../../app/Controller/PurchaseOrderController.php), [controller SO](../../app/Controller/SalesOrderController.php), [service PO](../../app/Service/PurchaseOrderService.php), [service SO](../../app/Service/SalesOrderService.php), dan [kontrak repository](../../app/Contract/).

Perbandingan dengan [diagram rancangan retrospektif](../planning/class-diagram-initial.md): rancangan konseptual hanya menunjukkan tiga layer dan kontrak repository. Implementasi as-built menambah pemisahan kontrak PO untuk `find()`, repository baca katalog, enum tipe movement, dan fake untuk unit test. Perbandingan ini menjelaskan perubahan desain, **bukan bukti** adanya diagram initial yang dibuat sebelum coding.
