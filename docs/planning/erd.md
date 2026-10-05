# ERD

ERD ringkas terbaru tersedia pada [dokumentasi aplikasi](../dokumentasi-summary.md#erd-ringkas). Relasi utama basis data:

```mermaid
erDiagram
    USERS ||--o{ TASKS : assignee_id
    PROJECTS ||--o{ TASKS : project_id
    SUPPLIERS ||--o{ PURCHASE_ORDERS : supplier_id
    WAREHOUSES ||--o{ PURCHASE_ORDERS : warehouse_id
    PURCHASE_ORDERS ||--|{ PURCHASE_ORDER_DETAILS : purchase_order_id
    PRODUCTS ||--o{ PURCHASE_ORDER_DETAILS : product_id
    CUSTOMERS ||--o{ SALES_ORDERS : customer_id
    WAREHOUSES ||--o{ SALES_ORDERS : warehouse_id
    SALES_ORDERS ||--|{ SALES_ORDER_DETAILS : sales_order_id
    PRODUCTS ||--o{ SALES_ORDER_DETAILS : product_id
    PRODUCTS ||--o{ STOCKS : product_id
    WAREHOUSES ||--o{ STOCKS : warehouse_id
    PRODUCTS ||--o{ STOCK_MOVEMENTS : product_id
    WAREHOUSES ||--o{ STOCK_MOVEMENTS : warehouse_id
```

`stocks` memiliki unique key `(product_id, warehouse_id)` agar satu produk hanya mempunyai satu saldo pada satu gudang. Constraint `current_stock >= 0` menjaga stok tidak negatif.
