# Royal SL E-commerce — Database Schema

Complete relational schema for the Laravel 12 REST API backend. All monetary columns are `decimal(12,2)`. All tables use `bigIncrements` primary keys and `timestamps()` unless noted. Soft deletes are marked `SD`.

## 1. Identity, Roles & Access

| Table | Key Columns |
|---|---|
| `users` (SD) | name, email (unique), phone, password, avatar, status(active,inactive,banned), email_verified_at |
| `roles` | Spatie: name, guard_name |
| `permissions` | Spatie: name, guard_name |
| `model_has_roles` / `model_has_permissions` / `role_has_permissions` | Spatie pivot tables |
| `personal_access_tokens` | Sanctum tokens |
| `addresses` | user_id, label, recipient_name, phone, line1, line2, city, state, postal_code, country, type(shipping,billing), is_default |

## 2. Catalog

| Table | Key Columns |
|---|---|
| `categories` (SD) | parent_id (self FK), name, slug (unique), description, image, is_active, sort_order |
| `brands` | name, slug (unique), logo, is_active |
| `suppliers` | name, company_name, email, phone, address, city, country, is_active |
| `products` (SD) | category_id, brand_id, supplier_id, name, slug (unique), sku (unique), barcode (unique), description, short_description, cost_price, selling_price, discount_price, tax_rate, unit, has_variants, is_active, is_featured, low_stock_threshold, weight, meta_title, meta_description, created_by |
| `product_variants` | product_id, sku (unique), barcode (unique), attributes(json), cost_price, selling_price, image, is_active |
| `product_images` | product_id, path, is_primary, sort_order |

## 3. Central Inventory

Every sales channel (website, Facebook, POS) and every stock movement reads/writes the **same** `stocks` ledger keyed by `(product_id, product_variant_id, warehouse_id)`, guaranteeing a single source of truth. `stock_movements` is the immutable audit trail; `InventoryService` is the only code path allowed to mutate `stocks`.

| Table | Key Columns |
|---|---|
| `warehouses` | name, code (unique), type(main,branch,outlet), address, phone, is_default, is_active |
| `stocks` | product_id, product_variant_id, warehouse_id, quantity, reserved_quantity — unique(product_id, product_variant_id, warehouse_id) |
| `stock_movements` | product_id, product_variant_id, warehouse_id, type(purchase,sale,return,damage,adjustment,transfer_in,transfer_out,initial), quantity_change, quantity_before, quantity_after, reference_type, reference_id, note, created_by |
| `suppliers` | (see catalog) |
| `purchases` | purchase_no (unique), supplier_id, warehouse_id, status(pending,ordered,received,cancelled), order_date, expected_date, received_date, subtotal, tax_amount, discount_amount, total_amount, notes, created_by |
| `purchase_items` | purchase_id, product_id, product_variant_id, quantity, received_quantity, unit_cost, subtotal |
| `stock_transfers` | transfer_no (unique), from_warehouse_id, to_warehouse_id, status(pending,in_transit,completed,cancelled), transfer_date, received_date, notes, created_by |
| `stock_transfer_items` | stock_transfer_id, product_id, product_variant_id, quantity, received_quantity |
| `stock_adjustments` | adjustment_no (unique), warehouse_id, reason(count,expired,other), status(pending,approved), notes, created_by |
| `stock_adjustment_items` | stock_adjustment_id, product_id, product_variant_id, quantity_change, before_quantity, after_quantity |
| `stock_returns` | return_no (unique), order_id (nullable), warehouse_id, type(customer_return,supplier_return), status(pending,approved,rejected), reason, notes, created_by |
| `stock_return_items` | stock_return_id, product_id, product_variant_id, quantity, condition(good,damaged) |
| `damages` | damage_no (unique), warehouse_id, reason, status(pending,approved), notes, created_by |
| `damage_items` | damage_id, product_id, product_variant_id, quantity, estimated_loss |
| `low_stock_alerts` | product_id, product_variant_id, warehouse_id, threshold, current_quantity, status(active,resolved), notified_at |

## 4. Sales (Website / Facebook / POS — unified)

| Table | Key Columns |
|---|---|
| `orders` (SD) | order_no (unique), source(website,facebook,pos), channel_reference, user_id, warehouse_id, status(pending,confirmed,processing,packed,shipped,delivered,completed,cancelled,returned), payment_status(unpaid,partial,paid,refunded), coupon_id, subtotal, discount_amount, tax_amount, shipping_amount, total_amount, paid_amount, currency, shipping_address_id, billing_address_id, customer_name, customer_phone, customer_email, served_by, created_by, cancelled_reason |
| `order_items` | order_id, product_id, product_variant_id, warehouse_id, product_name, sku, quantity, unit_price, discount, tax, subtotal |
| `order_status_histories` | order_id, status, note, changed_by |
| `carts` | user_id, session_id |
| `cart_items` | cart_id, product_id, product_variant_id, quantity |

## 5. Payments

| Table | Key Columns |
|---|---|
| `payment_methods` | name, code(cash,card,bank_transfer,cod,online_gateway), is_active, config(json) |
| `payments` | payment_no (unique), order_id, payment_method_id, amount, status(pending,completed,failed,refunded), transaction_id, paid_at, meta(json), created_by |

## 6. Coupons

| Table | Key Columns |
|---|---|
| `coupons` | code (unique), type(fixed,percentage), value, min_order_amount, max_discount_amount, usage_limit, usage_limit_per_user, used_count, starts_at, expires_at, is_active, applicable_to(all,category,product) |
| `coupon_products` | coupon_id, product_id |
| `coupon_categories` | coupon_id, category_id |
| `coupon_usages` | coupon_id, order_id, user_id, discount_amount |

## 7. Invoices, Logs, Notifications, Settings

| Table | Key Columns |
|---|---|
| `invoices` | invoice_no (unique), order_id, path, issued_at, due_at, total_amount, status(issued,paid,void) |
| `activity_log` | Spatie activity log (log_name, description, subject, causer, properties) |
| `notifications` | Laravel database notifications (id uuid, type, notifiable, data, read_at) |
| `settings` | key (unique), value, group |

## Key invariants

1. **Single inventory source of truth**: `stocks` is only ever mutated through `App\Services\InventoryService`. Purchases increase it, sales (any channel) decrease it, transfers move it between warehouses, adjustments/damages/returns correct it. Every mutation writes a `stock_movements` row inside the same DB transaction.
2. **Orders never touch stock directly**: `OrderService::placeOrder()` (website/Facebook) and `PosService::checkout()` (outlet POS) both delegate to `InventoryService::deduct()`, so quantity is never double-counted or channel-siloed.
3. **Low stock alerts** are (re)evaluated inside `InventoryService` after every mutation that can reduce quantity; when `quantity <= threshold` an alert row is upserted and a `LowStockNotification` is dispatched to admins/warehouse managers.
