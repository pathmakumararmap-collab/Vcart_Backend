# Royal SL E-commerce — API Backend

A production-ready Laravel 12 / PHP 8.4 REST API backend for a multi-channel e-commerce system: website storefront, Facebook order intake, and outlet POS — all sharing one **central inventory**.

## Architecture

- **Repository pattern** (`app/Repositories`) — interfaces + Eloquent implementations, bound in `RepositoryServiceProvider`.
- **Service layer** (`app/Services`) — all business logic and every stock mutation. `InventoryService` is the single source of truth for the `stocks` table; `OrderService`, `PosService`, `PurchaseService`, `StockTransferService`, `StockAdjustmentService`, `StockReturnService`, and `DamageService` all delegate to it.
- **Form Requests** for validation, **API Resources** for output shaping, **Policies** for authorization (backed by Spatie roles/permissions).
- **Sanctum** token authentication, **Spatie Permission** for roles/permissions, **Spatie Activitylog** for audit trails, **DomPDF** for invoices, **picqer/php-barcode-generator** for barcodes, **L5-Swagger** for OpenAPI docs.

See [`docs/DATABASE_SCHEMA.md`](docs/DATABASE_SCHEMA.md) and [`docs/ER_DIAGRAM.md`](docs/ER_DIAGRAM.md) for the full schema and entity-relationship diagrams.

### Why one inventory

Every sales channel — website checkout, imported Facebook orders, and outlet POS sales — is created through `OrderService::placeOrder()` (POS goes through `PosService`, which itself calls `OrderService`). That method is the *only* code path allowed to create an `Order`, and it always calls `InventoryService::deduct()` for every line item, against the same `stocks` row keyed by `(product_id, product_variant_id, warehouse_id)`. Purchases, transfers, adjustments, returns, and damages all go through the same `InventoryService`, and every mutation is logged to `stock_movements` as an immutable audit ledger. There is no other way to change a stock quantity in this codebase.

## Requirements

- PHP 8.4
- MySQL 8+
- Composer 2

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate

# Configure DB_* in .env for your MySQL instance, then:
php artisan migrate
php artisan db:seed

php artisan serve
```

Publish the Swagger UI docs at any time with:

```bash
php artisan l5-swagger:generate
# then visit /api/documentation
```

### Seeded accounts (all use password `password`)

| Role | Email |
|---|---|
| Admin | `admin@royalsl.test` |
| Manager | `manager@royalsl.test` |
| Warehouse Manager | `warehouse@royalsl.test` |
| POS Cashier | `cashier@royalsl.test` |
| Customer | `customer@royalsl.test` |

## Running tests

Tests run against a real MySQL database (matching production), not sqlite — see `phpunit.xml`.

```bash
mysql -u root -e "CREATE DATABASE royal_sl_ecommerce_testing; \
  CREATE USER IF NOT EXISTS 'royal'@'localhost' IDENTIFIED BY 'royal_secret'; \
  GRANT ALL PRIVILEGES ON royal_sl_ecommerce_testing.* TO 'royal'@'localhost';"

php artisan test
```

37 tests cover: multi-channel inventory sharing (the core requirement — a dedicated test places a website order, a Facebook order, and a POS sale in sequence and asserts they deduct the same stock row), coupons, purchasing/receiving, stock transfers, damages, returns, low-stock alerts, auth, catalog browsing, admin CRUD + authorization, and reporting.

## API documentation

- **Swagger / OpenAPI UI**: `GET /api/documentation` (generated from PHP attributes on controllers via `darkaonline/l5-swagger`; raw spec at `storage/api-docs/api-docs.json`).
- **Human-readable API reference**: [`docs/API_DOCUMENTATION.md`](docs/API_DOCUMENTATION.md).
- **Postman collection**: [`docs/postman_collection.json`](docs/postman_collection.json) + [`docs/postman_environment.json`](docs/postman_environment.json) — 84 requests across 17 folders covering every API area below.
- **Installation guide**: [`docs/INSTALLATION_GUIDE.md`](docs/INSTALLATION_GUIDE.md) — step-by-step local setup, deployment notes, and troubleshooting.

All endpoints are versioned under `/api/v1`. Authenticate with `POST /api/v1/auth/login`, then send `Authorization: Bearer {token}` on subsequent requests.

## API surface

| Area | Base path | Notes |
|---|---|---|
| Auth | `/auth` | register, login, logout, me |
| Catalog (public) | `/catalog` | product/category browsing, no auth |
| Cart | `/cart` | guest carts identified via `X-Cart-Token` header (stateless — no PHP sessions) |
| Customer | `/customer` | profile, addresses, checkout (website orders), order history |
| Admin — Products | `/admin/products`, `/admin/categories`, `/admin/brands` | full CRUD, barcode generation |
| Admin — Coupons | `/admin/coupons` | fixed/percentage, usage limits, product/category scoping |
| Admin — Users & Access | `/admin/users`, `/admin/roles`, `/admin/permissions`, `/admin/activity-logs` | role/permission management, audit trail |
| Admin — Orders | `/admin/orders` | cross-channel order management + `/admin/orders/facebook` for Facebook order import |
| POS | `/pos` | `checkout` (sale + payment + invoice in one call), barcode `lookup` |
| Inventory | `/inventory` | warehouses, suppliers, stock levels, low-stock alerts, movement ledger, purchases (+receive), stock transfers (+receive), adjustments, returns, damages |
| Reports | `/reports` | sales, orders, products, stock |
| Notifications | `/notifications` | database notifications (low stock, order placed) |
| Invoices | `/invoices/{id}/download` | PDF download |

## Roles & permissions

Seeded via `RolePermissionSeeder`: `admin` (all permissions), `manager` (everything except user management), `warehouse_manager` (inventory operations), `pos_cashier` (POS + read-only catalog), `customer` (no elevated permissions — ownership-based policies only).

## Project layout

```
app/
  Events/            LowStockDetected, OrderPlaced
  Exceptions/         InsufficientStockException, InvalidCouponException
  Http/
    Controllers/Api/  Auth, Catalog, Customer, Admin, Orders, Pos, Inventory, Reports
    Requests/         Form Request validation, one namespace per resource
    Resources/        API Resource transformers
  Listeners/           SendLowStockNotification, SendOrderPlacedNotification
  Models/              Eloquent models with relationships/casts/scopes
  Notifications/       LowStockNotification, OrderPlacedNotification
  Policies/            Authorization policies (auto-discovered)
  Repositories/        Contracts + Eloquent implementations
  Services/            Business logic — InventoryService is the inventory source of truth
database/
  factories/           Model factories for every core entity
  migrations/           Full schema (see docs/DATABASE_SCHEMA.md)
  seeders/              Roles/permissions, warehouses, payment methods, users, catalog, coupons
docs/
  DATABASE_SCHEMA.md    Full table-by-table schema reference
  ER_DIAGRAM.md          Mermaid ER diagram + order-flow diagram
  postman_collection.json / postman_environment.json
tests/
  Feature/               End-to-end API tests
  Unit/                  Service-level unit tests
```
