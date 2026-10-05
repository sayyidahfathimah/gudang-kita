# Class Diagram As-Built — Gudang Kita

Diagram ini mengikuti kelas yang tersedia pada kode saat ini. Panah `..|>` berarti implementasi interface; panah `-->` berarti penggunaan kelas konkret; panah `..>` menunjukkan Service menerima kontrak melalui constructor.

```mermaid
classDiagram
    class SalesOrderController
    class PurchaseOrderController
    class ReportController
    class SalesOrderService
    class PurchaseOrderService
    class ReportAccessPolicy
    class SalesOrderRepositoryInterface { <<interface>> }
    class PurchaseOrderRepositoryInterface { <<interface>> }
    class PurchaseOrderWorkflowRepositoryInterface { <<interface>> }
    class SalesOrderRepository
    class PurchaseOrderRepository
    class ReportRepository
    class InventoryRepository
    class InMemorySalesOrderRepository
    class InMemoryPurchaseOrderRepository
    class StockMovementType { <<enumeration>> }
    class PDO

    SalesOrderController --> SalesOrderService : concrete
    SalesOrderController --> SalesOrderRepository : concrete
    SalesOrderController --> InventoryRepository : concrete
    PurchaseOrderController --> PurchaseOrderService : concrete
    PurchaseOrderController --> PurchaseOrderRepository : concrete
    PurchaseOrderController --> InventoryRepository : concrete
    ReportController --> ReportAccessPolicy : concrete
    ReportController --> ReportRepository : concrete
    SalesOrderService ..> SalesOrderRepositoryInterface : constructor interface
    PurchaseOrderService ..> PurchaseOrderRepositoryInterface : constructor interface
    PurchaseOrderWorkflowRepositoryInterface --|> PurchaseOrderRepositoryInterface
    SalesOrderRepository ..|> SalesOrderRepositoryInterface
    PurchaseOrderRepository ..|> PurchaseOrderWorkflowRepositoryInterface
    InMemorySalesOrderRepository ..|> SalesOrderRepositoryInterface : unit fake
    InMemoryPurchaseOrderRepository ..|> PurchaseOrderRepositoryInterface : unit fake
    SalesOrderRepository --> StockMovementType
    PurchaseOrderRepository --> StockMovementType
    SalesOrderRepository --> PDO
    PurchaseOrderRepository --> PDO
    ReportRepository --> PDO
```

Service hanya mengetahui kontrak repository. Implementasi MySQL menyimpan perubahan order, stok, dan ledger di dalam transaksi; fake digunakan oleh unit test tanpa MySQL. Controller saat ini masih menerima atau membuat beberapa repository konkret karena composition root manual belum diterapkan secara menyeluruh.

Diagram initial historis belum ditemukan. Karena itu, perubahan waktu antara diagram sebelum coding dan diagram as-built tidak dapat dibuktikan; [catatan diagram awal](../planning/class-diagram-initial.md) mencatat keterbatasan tersebut.
