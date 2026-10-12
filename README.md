# Mini ERP – Inventory & Purchase Order Management

A Laravel 12 application for managing suppliers, product inventory, purchase orders, and supplier spending. Includes a Blade-based admin interface and a REST API secured with Laravel Sanctum.

## Features

- Admin authentication and protected web pages
- Supplier and product management data
- Inventory listing with low-stock indicators
- Purchase order creation with multiple products and calculated totals
- Purchase order lifecycle: `DRAFT` → `APPROVED` → `RECEIVED` or `CANCELLED`
- Stock increases only when an order transitions to `RECEIVED`
- Transaction-based purchase order operations
- REST API authentication using Sanctum bearer tokens
- Paginated inventory and supplier-spend reports
- Automated feature tests

## Technology Stack

- PHP
- Laravel 12
- MySQL
- Blade templates and Bootstrap
- Laravel Sanctum
- PHPUnit

## Requirements

- PHP version compatible with Laravel 12
- Composer
- MySQL

## Installation

1. Clone or copy the project and open its directory.

   ```bash
   cd mini-erp
   ```

2. Install PHP dependencies.

   ```bash
   composer install
   ```

3. Create the environment file.

   ```bash
   copy .env.example .env
   ```

   On macOS/Linux, use `cp .env.example .env`.

4. Generate the application key.

   ```bash
   php artisan key:generate
   ```

5. Create a MySQL database, then configure the following values in `.env`:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=mini_erp
   DB_USERNAME=your_database_username
   DB_PASSWORD=your_database_password
   ```

   Replace the database name and credentials with your local configuration.

6. Run migrations and seed the initial data.

   ```bash
   php artisan migrate --seed
   ```

7. Start the development server.

   ```bash
   php artisan serve
   ```

8. Open `http://127.0.0.1:8000/login`.

## Admin Login

The `AdminUserSeeder` creates or updates the following administrator:

- **Email:** `admin@mini-erp.test`
- **Password:** `admin@123`

These are development credentials.

## Purchase Order Lifecycle

1. Create a purchase order in `DRAFT` status.
2. Approve the order to move it to `APPROVED`.
3. Receive the order to move it to `RECEIVED`.
4. Alternatively, cancel an eligible order by moving it to `CANCELLED`.

Inventory stock is increased only when an approved purchase order is received. Receiving and cancellation are terminal states; invalid status transitions are rejected.

Purchase order creation and receiving operations use database transactions to maintain data consistency.

## REST API

Protected endpoints require a Sanctum bearer token:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

### Authentication

| Method | Endpoint | Description | Authentication |
|---|---|---|---|
| POST | `/api/login` | Obtain an API token | No |
| POST | `/api/logout` | Revoke the current token | Yes |
| GET | `/api/user` | Get the authenticated user | Yes |

### Inventory

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/inventory` | List active inventory with pagination |
| GET | `/api/inventory/low-stock` | List active products at or below their low-stock threshold |

### Purchase Orders

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/purchase-orders` | Create a draft purchase order |
| PATCH | `/api/purchase-orders/{id}/status` | Update an eligible order's status |

### Reports

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/reports/supplier-spend` | Paginated spending totals for received orders, grouped by supplier |

### Example: Log in

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\":\"admin@mini-erp.test\",\"password\":\"admin@123\"}"
```

Copy the returned token and use it in the `Authorization` header for protected endpoints.

### Example: Get inventory

```bash
curl http://127.0.0.1:8000/api/inventory \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Example: Create a purchase order

```bash
curl -X POST http://127.0.0.1:8000/api/purchase-orders \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d "{\"supplier_id\":1,\"items\":[{\"product_id\":1,\"quantity\":2}]}"
```

A successful creation returns HTTP `201 Created`. Invalid input returns a validation error.

### Example: Receive an approved order

```bash
curl -X PATCH http://127.0.0.1:8000/api/purchase-orders/1/status \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d "{\"status\":\"RECEIVED\"}"
```

Replace `1` with the ID of an order that is currently `APPROVED`.

## Automated Tests

Run the test suite:

```bash
php artisan test
```

The current test suite covers purchase order totals, duplicate and inactive products, stock updates on receiving, terminal status restrictions, API authentication, quantity validation, and admin login access.

## Database Design and Indexing

The application uses related tables for suppliers, products, purchase orders, and purchase order items.

Indexes are used for common lookups and filtering, including:

- Unique product SKUs and purchase order numbers
- Supplier and product foreign keys
- Product names and active status
- Purchase order status and creation time
- Supplier and order status combinations
- Inventory stock and low-stock threshold fields

Foreign-key constraints help maintain referential integrity. Purchase order totals and line-item subtotals are stored as decimal values, and inventory changes are handled transactionally.

## Notes

- Seeded data is intended for local development and demonstration.
- Configure valid database credentials before running migrations.
