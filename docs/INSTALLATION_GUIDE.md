# Installation Guide — Royal SL E-commerce API

Step-by-step setup for local development and production deployment.

## 1. Requirements

| Requirement | Version |
|---|---|
| PHP | 8.4 (with `pdo_mysql`, `gd` or `imagick`, `mbstring`, `bcmath`, `zip` extensions) |
| MySQL | 8.0+ (MariaDB 10.6+ also works) |
| Composer | 2.x |
| Node.js | 18+ (optional — only needed if you build front-end assets) |

## 2. Extract the project

Unzip the archive and move into the project directory:

```bash
unzip royal-sl-ecommerce.zip -d royal-sl-ecommerce
cd royal-sl-ecommerce
```

## 3. Install PHP dependencies

```bash
composer install
```

This installs Laravel 12, Sanctum, Spatie Permission, Spatie Activitylog, DomPDF, the barcode generator, and L5-Swagger.

## 4. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your database connection:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=royal_sl_ecommerce
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

Also review (defaults are sensible for local dev):

```env
L5_SWAGGER_CONST_HOST=http://localhost:8000
L5_SWAGGER_GENERATE_ALWAYS=true

QUEUE_CONNECTION=database   # low-stock / order-placed notifications are queued
SESSION_DRIVER=database
CACHE_STORE=database
```

## 5. Create the database

```bash
mysql -u root -p -e "CREATE DATABASE royal_sl_ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## 6. Run migrations and seed sample data

```bash
php artisan migrate
php artisan db:seed
```

This creates all 51 tables (see [`DATABASE_SCHEMA.md`](DATABASE_SCHEMA.md)) and seeds:

- Roles & permissions (`admin`, `manager`, `warehouse_manager`, `pos_cashier`, `customer`)
- 3 warehouses (1 central "main" warehouse + 2 outlets)
- 5 payment methods (cash, card, bank transfer, COD, online gateway)
- 5 demo user accounts (see table below)
- 24 demo products across 5 categories / 4 brands, with stock in the central warehouse
- 2 demo coupons (`WELCOME10`, `FLAT500`)

### Seeded accounts (password: `password` for all)

| Role | Email |
|---|---|
| Admin | `admin@royalsl.test` |
| Manager | `manager@royalsl.test` |
| Warehouse Manager | `warehouse@royalsl.test` |
| POS Cashier | `cashier@royalsl.test` |
| Customer | `customer@royalsl.test` |

## 7. Link storage (for product images, avatars, brand logos)

```bash
php artisan storage:link
```

## 8. Queue worker (for async notifications)

Order-placed and low-stock notifications are queued. Run a worker in a separate process (or use a process manager like Supervisor in production):

```bash
php artisan queue:work
```

If you don't want to run a worker for local testing, set `QUEUE_CONNECTION=sync` in `.env` instead — notifications will then send synchronously.

## 9. Start the application

```bash
php artisan serve
```

The API is now available at `http://localhost:8000/api/v1`. Try:

```bash
curl http://localhost:8000/api/ping
```

## 10. Generate / view API documentation

```bash
php artisan l5-swagger:generate
```

Then open `http://localhost:8000/api/documentation` in a browser for the interactive Swagger UI, or import [`postman_collection.json`](postman_collection.json) + [`postman_environment.json`](postman_environment.json) into Postman.

## 11. Run the test suite

Tests run against a real MySQL database (mirroring production behavior — enums, foreign keys, transactions):

```bash
mysql -u root -p -e "
  CREATE DATABASE royal_sl_ecommerce_testing;
  CREATE USER IF NOT EXISTS 'royal'@'localhost' IDENTIFIED BY 'royal_secret';
  GRANT ALL PRIVILEGES ON royal_sl_ecommerce_testing.* TO 'royal'@'localhost';
"

php artisan test
```

If you use different testing credentials, update the `<php>` block in `phpunit.xml` accordingly.

## 12. Production deployment notes

- Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`.
- Run `composer install --no-dev --optimize-autoloader`.
- Cache configuration and routes: `php artisan config:cache && php artisan route:cache`.
- Run `php artisan storage:link` on the production host.
- Point `QUEUE_CONNECTION` at a real queue driver (database is fine at moderate scale; Redis/SQS for higher throughput) and run the worker under a process manager (Supervisor, systemd).
- Serve over HTTPS — Sanctum tokens are bearer tokens and must not travel over plain HTTP in production.
- The GitHub Actions workflow in `.github/workflows/tests.yml` runs the full test suite against a MySQL 8 service container on every push/PR — use it as a template for your CI provider if not using GitHub Actions.

## Troubleshooting

| Symptom | Fix |
|---|---|
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL isn't running or `DB_HOST`/`DB_PORT` in `.env` are wrong. |
| `Class "Barryvdh\DomPDF\Facade\Pdf" not found` | Run `composer install` again — a dependency didn't install. |
| Notifications never arrive | Either run `php artisan queue:work`, or set `QUEUE_CONNECTION=sync` in `.env`. |
| `419` / CSRF errors calling the API | The API is stateless (Sanctum bearer tokens) — don't send `X-XSRF-TOKEN`, just `Authorization: Bearer {token}`. |
| Uploaded images return 404 | Run `php artisan storage:link`. |
