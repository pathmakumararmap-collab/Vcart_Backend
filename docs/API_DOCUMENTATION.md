# API Documentation — Royal SL E-commerce

This is the human-readable reference for the REST API. For interactive, always-up-to-date documentation generated from the code, run `php artisan l5-swagger:generate` and open `/api/documentation`, or import [`postman_collection.json`](postman_collection.json) into Postman.

- **Base URL**: `http://localhost:8000/api/v1` (adjust host for your environment)
- **Format**: JSON request/response bodies
- **Auth**: Laravel Sanctum bearer tokens — `Authorization: Bearer {token}`
- **Errors**: `422` for validation, `401` unauthenticated, `403` unauthorized, `404` not found. Validation errors follow `{"message": "...", "errors": {"field": ["..."]}}`.

## Authentication

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/auth/register` | — | Register a customer account, returns a token |
| POST | `/auth/login` | — | Login, returns a token |
| POST | `/auth/logout` | ✓ | Revoke the current token |
| GET | `/auth/me` | ✓ | Current authenticated user + roles |

**Login example**

```http
POST /api/v1/auth/login
Content-Type: application/json

{"email": "admin@royalsl.test", "password": "password"}
```

```json
{
  "user": { "id": 1, "name": "Royal SL Admin", "email": "admin@royalsl.test", "roles": ["admin"] },
  "token": "1|A0igtPjTwqkpURo8Jq..."
}
```

## Catalog (public, no auth)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/catalog/products` | List active products. Query: `keyword`, `category_id`, `brand_id`, `min_price`, `max_price`, `sort` (`latest`\|`price_asc`\|`price_desc`\|`name`), `per_page` |
| GET | `/catalog/products/{slug}` | Product detail |
| GET | `/catalog/categories` | Category tree |

## Cart

Guests are identified by a client-generated `X-Cart-Token` header (no PHP sessions — this is a stateless API). Authenticated users' carts are tied to their account automatically.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/cart` | View cart contents |
| POST | `/cart/items` | Add item — `{product_id, product_variant_id?, quantity}` |
| PUT | `/cart/items/{item}` | Update quantity — `{quantity}` (0 removes it) |
| DELETE | `/cart/items/{item}` | Remove item |
| DELETE | `/cart` | Clear cart |

## Customer (auth required)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/customer/profile` | Get profile |
| PUT | `/customer/profile` | Update profile (name, email, phone, avatar) |
| GET / POST / PUT / DELETE | `/customer/addresses[/{id}]` | Manage saved addresses |
| POST | `/customer/checkout` | **Place a website order** (see below) |
| GET | `/customer/orders` | List own orders |
| GET | `/customer/orders/{order}` | Order detail |
| POST | `/customer/orders/{order}/cancel` | Cancel an order — `{reason}` |

**Checkout example** (website channel)

```http
POST /api/v1/customer/checkout
Authorization: Bearer {token}

{
  "items": [{"product_id": 3, "quantity": 2}],
  "customer_name": "Jane Doe",
  "customer_phone": "0771112223",
  "coupon_code": "WELCOME10",
  "shipping_address_id": 5
}
```

This deducts stock from the default (central) warehouse via `InventoryService`, applies the coupon, generates an invoice, and returns the created order.

## Admin — Catalog

| Resource | Endpoints |
|---|---|
| Products | `GET/POST /admin/products`, `GET/PUT/DELETE /admin/products/{id}`, `GET /admin/products/{id}/barcode` (SVG barcode image) |
| Categories | `GET/POST /admin/categories`, `GET/PUT/DELETE /admin/categories/{id}` |
| Brands | `GET/POST /admin/brands`, `GET/PUT/DELETE /admin/brands/{id}` |
| Coupons | `GET/POST /admin/coupons`, `GET/PUT/DELETE /admin/coupons/{id}` |

Products support variants (`variants[]` with `sku`, `barcode`, `attributes`, prices) and multipart image uploads (`images[]`) on create.

## Admin — Users & Access Control

| Method | Endpoint | Description |
|---|---|---|
| GET/POST | `/admin/users` | List / create staff & customer accounts |
| GET/PUT/DELETE | `/admin/users/{id}` | Manage a user, including `roles[]` reassignment |
| GET/POST | `/admin/roles` | List / create roles with `permissions[]` |
| PUT | `/admin/roles/{role}` | Sync a role's permissions |
| GET | `/admin/permissions` | List all permission names |
| GET | `/admin/activity-logs` | Audit trail (who changed what) — filter by `subject_type`, `causer_id` |
| GET/POST/PUT | `/admin/payment-methods` | Manage payment methods |

Seeded roles: `admin`, `manager`, `warehouse_manager`, `pos_cashier`, `customer`.

## Admin — Orders (all channels)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/admin/orders` | List/search orders. Query: `source` (`website`\|`facebook`\|`pos`), `status`, `payment_status`, `warehouse_id`, `date_from`, `date_to`, `keyword` |
| POST | `/admin/orders/facebook` | **Import a Facebook order** — deducts the same central inventory as website/POS |
| GET | `/admin/orders/{order}` | Order detail |
| PUT | `/admin/orders/{order}/status` | Update status — `{status, note?}` |
| POST | `/admin/orders/{order}/cancel` | Cancel and restock — `{reason}` |
| POST | `/admin/orders/{order}/payments` | Record a payment — `{payment_method_id, amount, transaction_id?}` |
| POST | `/admin/orders/{order}/invoice` | (Re)generate the PDF invoice |

## POS

| Method | Endpoint | Description |
|---|---|---|
| POST | `/pos/checkout` | **Complete an in-store sale**: creates the order, deducts stock, records payment, generates an invoice — all in one call |
| GET | `/pos/lookup/{barcode}` | Look up a product by scanned barcode |

**POS checkout example**

```http
POST /api/v1/pos/checkout
Authorization: Bearer {cashier_token}

{
  "warehouse_id": 2,
  "items": [{"product_id": 3, "quantity": 1}],
  "payment_method_id": 1,
  "amount_tendered": 1000
}
```

Response includes the completed order, the generated invoice, and `change_due`.

## Inventory

All inventory endpoints route through `InventoryService` — see [`ER_DIAGRAM.md`](ER_DIAGRAM.md) for the flow diagram.

| Resource | Endpoints |
|---|---|
| Warehouses | `GET/POST /inventory/warehouses`, `GET/PUT/DELETE /inventory/warehouses/{id}` |
| Suppliers | `GET/POST /inventory/suppliers`, `GET/PUT/DELETE /inventory/suppliers/{id}` |
| Stock | `GET /inventory/stock` (filter `warehouse_id`, `product_id`), `GET /inventory/stock/low-stock`, `GET /inventory/stock/movements` (audit ledger) |
| Purchases | `GET/POST /inventory/purchases`, `GET /inventory/purchases/{id}`, `POST /inventory/purchases/{id}/receive`, `POST /inventory/purchases/{id}/cancel` — receiving is what actually increases stock |
| Stock Transfers | `GET/POST /inventory/stock-transfers`, `GET /inventory/stock-transfers/{id}`, `POST /inventory/stock-transfers/{id}/receive` — stock leaves the source warehouse on dispatch, arrives at the destination on receipt |
| Stock Adjustments | `GET/POST /inventory/stock-adjustments`, `GET /inventory/stock-adjustments/{id}` — set an exact counted quantity |
| Stock Returns | `GET/POST /inventory/stock-returns`, `GET /inventory/stock-returns/{id}` — items in `good` condition are restocked; `damaged` items are not |
| Damages | `GET/POST /inventory/damages`, `GET /inventory/damages/{id}` — removes stock and records estimated loss |

## Reports

| Method | Endpoint | Description |
|---|---|---|
| GET | `/reports/sales` | Revenue, discounts, tax, breakdown by channel and by day. Query: `from`, `to`, `source`, `warehouse_id` |
| GET | `/reports/orders` | Breakdown by order status and payment status |
| GET | `/reports/products` | Top-selling products + total stock cost valuation |
| GET | `/reports/stock` | Total units, low-stock item count/list, recent stock movements |

## Notifications & Invoices

| Method | Endpoint | Description |
|---|---|---|
| GET | `/notifications` | List the authenticated user's notifications + unread count |
| PUT | `/notifications/{id}/read` | Mark one as read |
| PUT | `/notifications/read-all` | Mark all as read |
| GET | `/invoices/{invoice}/download` | Download the invoice PDF |

## Permissions reference

Controllers authorize via Spatie permissions (`$user->can('resource.action')`) and/or Policies. Key permission names: `products.*`, `categories.*`, `brands.*`, `coupons.*`, `users.*`, `orders.*`, `warehouses.*`, `suppliers.*`, `purchases.*` (+`.receive`), `stock-transfers.*` (+`.receive`), `stock-adjustments.*`, `stock-returns.*`, `damages.*`, `stock.view`, `payments.*`, `pos.sell`, `reports.view`, `activity-logs.view`. Full list and role assignments: `database/seeders/RolePermissionSeeder.php`.
