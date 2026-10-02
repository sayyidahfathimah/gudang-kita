# Gudang Kita — Inventory & Project Management System

Final Project Training Junior Programmer 2026 — PT Neuronworks Indonesia.

## Scope
Gudang Kita menggabungkan **Inventory Management** dan **Project Activity & Task Management** dalam satu aplikasi.

### Modul
- **Dashboard**: ringkasan project, task, inventory, stok minimum, dan transaksi terbaru.
- **Master Data**: Users, Produk, Customer, Supplier, Gudang.
- **Transaksi**: Purchase Order dan Sales Order.
- **Inventory**: Stok dan Stock Movement.
- **Report**: laporan stok, purchase order, sales order, dan stock movement.
- **Project Management**: Projects dan Tasks.

### Role
- **Admin**: manage users, master data, transaksi, inventory, project, dan task.
- **Member**: akses project/task sesuai authorization yang tersedia.

## Technology
- HTML, CSS, Vanilla JavaScript
- PHP 8.2+ Native OOP
- MySQL 8
- Docker Compose
- PHPUnit 10+
- PDO prepared statement

## Database
Database MySQL yang digunakan oleh aplikasi adalah:

```text
inventory_db
```

Database configuration is supplied through the local `.env` file:

```text
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD
```

Port MySQL dari host Mac:

```text
localhost:3307
```

## Run with Docker
Jalankan dari root project:

```bash
docker compose up -d --build
```

Buka:

```text
http://localhost:8080
```

Database dan seed otomatis dibuat oleh `database/schema-and-seed.sql` pada first run volume MySQL.

### Reset database development
> Perintah ini menghapus volume database Docker. Gunakan hanya jika data development boleh dihapus.

```bash
docker compose down -v
docker compose up -d --build
```

### Check container

```bash
docker compose ps
docker compose logs app --tail=50
docker compose logs db --tail=50
```

## Local accounts
Create local development accounts through the database seed or user management page. Do not publish account passwords.

## Inventory Flow

```text
Purchase Order → Receive → Stock In → Stock Movement → Stock
Sales Order    → Complete → Stock Out → Stock Movement → Stock
```

Stok disimpan per kombinasi produk dan gudang melalui tabel `stocks`, sedangkan histori perubahan disimpan pada `stock_movements`.

## Database Tables

```text
users
projects
tasks
products
suppliers
customers
warehouses
purchase_orders
purchase_order_details
sales_orders
sales_order_details
stocks
stock_movements
```

## Unit Test
Jika dependency Composer tersedia:

```bash
composer install
vendor/bin/phpunit
```

## Project Structure

```text
public/       entry point + assets
app/          controller, repository, validation, security
views/        UI templates
config/       configuration
database/     schema + seed
tests/        unit tests
docs/         planning + testing evidence
```

## UI
Nama aplikasi: **Gudang Kita**

Tema: **pink lembut**.
