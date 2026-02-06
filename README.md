# Sales API Documentation

## Table of Contents
- [Overview](#overview)
- [Authentication](#authentication)
- [Products API](#products-api)
- [Sales API](#sales-api)
- [Sync API](#sync-api)
- [Error Handling](#error-handling)

---

## Overview

This API provides endpoints for managing products and sales. It includes offline synchronization capabilities with conflict resolution.

**BASE URL**: `http://localhost:8000/api/v1`

**Response Format**: All responses are in JSON format with a consistent structure:
```json
{
  "success": true|false,
  "message": "Optional message",
  "data": {}
}
```

---

## Authentication

All endpoints except registration and login require authentication using Laravel Sanctum tokens.

### Register

Create a new user account.

**Endpoint**: `POST /auth/register`

**Request Body**:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePassword123",
  "password_confirmation": "SecurePassword123"
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2026-02-06T10:00:00.000000Z",
    "updated_at": "2026-02-06T10:00:00.000000Z"
  },
  "token": "1|abc123def456...",
  "token_type": "Bearer"
}
```

---

### Login

Authenticate and receive an access token.

**Endpoint**: `POST /auth/login`

**Request Body**:
```json
{
  "email": "john@example.com",
  "password": "SecurePassword123"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "token": "2|xyz789abc123...",
  "token_type": "Bearer"
}
```

**Error Response** (422 Unprocessable Entity):
```json
{
  "success": false,
  "message": "The provided credentials are incorrect."
}
```

**Rate Limiting**: Maximum 5 attempts per 5 minutes per email/IP combination.

---

### Get Current User

Get authenticated user information.

**Endpoint**: `GET /auth/me`

**Headers**:
```
Authorization: Bearer {token}
```

**Response** (200 OK):
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "email_verified_at": null,
  "created_at": "2026-02-06T10:00:00.000000Z",
  "updated_at": "2026-02-06T10:00:00.000000Z"
}
```

---

### Logout

Revoke the current access token.

**Endpoint**: `POST /auth/logout`

**Headers**:
```
Authorization: Bearer {token}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## Products API

Manage product inventory.

### List Products

Get a paginated list of all products.

**Endpoint**: `GET /products`

**Headers**:
```
Authorization: Bearer {token}
```

**Query Parameters**:
- `per_page` (optional): Number of items per page (default: 15)
- `page` (optional): Page number (default: 1)
- `search` (optional): Search term for name or description
- `in_stock` (optional): Filter products with stock > 0 (any value)

**Example Request**:
```
GET /products?per_page=10&search=laptop&in_stock=1
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Laptop HP ProBook",
        "description": "15-inch business laptop",
        "price": "850.00",
        "stock": 15,
        "created_at": "2026-02-06T10:00:00.000000Z",
        "updated_at": "2026-02-06T10:00:00.000000Z"
      },
      {
        "id": 2,
        "name": "Wireless Mouse",
        "description": "Ergonomic wireless mouse",
        "price": "25.50",
        "stock": 50,
        "created_at": "2026-02-06T10:00:00.000000Z",
        "updated_at": "2026-02-06T10:00:00.000000Z"
      }
    ],
    "first_page_url": "http://localhost/products?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost/products?page=1",
    "next_page_url": null,
    "path": "http://localhost/products",
    "per_page": 10,
    "prev_page_url": null,
    "to": 2,
    "total": 2
  }
}
```

---

### Create Product

Add a new product to inventory.

**Endpoint**: `POST /products`

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body**:
```json
{
  "name": "Laptop HP ProBook",
  "description": "15-inch business laptop with Intel i5",
  "price": 850.00,
  "stock": 15
}
```

**Validation Rules**:
- `name`: required, string, max 255 characters
- `description`: required, string, max 1000 characters
- `price`: required, numeric, minimum 0
- `stock`: required, integer, minimum 0

**Response** (201 Created):
```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "id": 1,
    "name": "Laptop HP ProBook",
    "description": "15-inch business laptop with Intel i5",
    "price": "850.00",
    "stock": 15,
    "created_at": "2026-02-06T10:00:00.000000Z",
    "updated_at": "2026-02-06T10:00:00.000000Z"
  }
}
```

---

### Get Product

Retrieve details of a specific product.

**Endpoint**: `GET /products/{id}`

**Headers**:
```
Authorization: Bearer {token}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Laptop HP ProBook",
    "description": "15-inch business laptop with Intel i5",
    "price": "850.00",
    "stock": 15,
    "created_at": "2026-02-06T10:00:00.000000Z",
    "updated_at": "2026-02-06T10:00:00.000000Z"
  }
}
```

**Error Response** (404 Not Found):
```json
{
  "message": "No query results for model [App\\Models\\Product] {id}"
}
```

---

### Update Product

Update an existing product.

**Endpoint**: `PUT /products/{id}`

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body** (all fields optional):
```json
{
  "name": "Laptop HP ProBook 450",
  "price": 899.99,
  "stock": 20
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Product updated successfully",
  "data": {
    "id": 1,
    "name": "Laptop HP ProBook 450",
    "description": "15-inch business laptop with Intel i5",
    "price": "899.99",
    "stock": 20,
    "created_at": "2026-02-06T10:00:00.000000Z",
    "updated_at": "2026-02-06T11:30:00.000000Z"
  }
}
```

---

### Delete Product

Remove a product from inventory.

**Endpoint**: `DELETE /products/{id}`

**Headers**:
```
Authorization: Bearer {token}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Product deleted successfully"
}
```

**Error Response** (422 Unprocessable Entity):
```json
{
  "success": false,
  "message": "Cannot delete product with existing sales"
}
```

> [!WARNING]
> Products with existing sales cannot be deleted to maintain data integrity.

---

## Sales API

Manage sales transactions.

### List Sales

Get a paginated list of the authenticated user's sales.

**Endpoint**: `GET /sales`

**Headers**:
```
Authorization: Bearer {token}
```

**Query Parameters**:
- `per_page` (optional): Number of items per page (default: 15)
- `page` (optional): Page number (default: 1)
- `status` (optional): Filter by status (pending, completed, cancelled)
- `date_from` (optional): Filter sales from this date (YYYY-MM-DD)
- `date_to` (optional): Filter sales until this date (YYYY-MM-DD)

**Example Request**:
```
GET /sales?status=completed&date_from=2026-02-01&date_to=2026-02-06
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "9d4e8c12-3f45-4a1b-8e9d-1234567890ab",
        "user_id": 1,
        "total": "1725.50",
        "status": "completed",
        "created_at": "2026-02-06T10:00:00.000000Z",
        "updated_at": "2026-02-06T10:00:00.000000Z",
        "deleted_at": null,
        "user": {
          "id": 1,
          "name": "John Doe",
          "email": "john@example.com"
        },
        "products": [
          {
            "id": 1,
            "name": "Laptop HP ProBook",
            "price": "850.00",
            "pivot": {
              "sale_id": "9d4e8c12-3f45-4a1b-8e9d-1234567890ab",
              "product_id": 1,
              "quantity": 2,
              "unit_price": "850.00",
              "subtotal": "1700.00"
            }
          },
          {
            "id": 2,
            "name": "Wireless Mouse",
            "price": "25.50",
            "pivot": {
              "sale_id": "9d4e8c12-3f45-4a1b-8e9d-1234567890ab",
              "product_id": 2,
              "quantity": 1,
              "unit_price": "25.50",
              "subtotal": "25.50"
            }
          }
        ]
      }
    ],
    "first_page_url": "http://localhost/sales?page=1",
    "from": 1,
    "last_page": 1,
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

---

### Create Sale

Create a new sale transaction.

**Endpoint**: `POST /sales`

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body**:
```json
{
  "products": [
    {
      "product_id": 1,
      "quantity": 2
    },
    {
      "product_id": 2,
      "quantity": 1
    }
  ],
  "status": "pending"
}
```

**Validation Rules**:
- `products`: required, array, minimum 1 item
- `products.*.product_id`: required, must exist in products table
- `products.*.quantity`: required, integer, minimum 1
- `status`: optional, must be one of: pending, completed, cancelled (default: pending)

**Response** (201 Created):
```json
{
  "success": true,
  "message": "Sale created successfully",
  "data": {
    "id": "9d4e8c12-3f45-4a1b-8e9d-1234567890ab",
    "user_id": 1,
    "total": "1725.50",
    "status": "pending",
    "created_at": "2026-02-06T10:00:00.000000Z",
    "updated_at": "2026-02-06T10:00:00.000000Z",
    "products": [
      {
        "id": 1,
        "name": "Laptop HP ProBook",
        "pivot": {
          "quantity": 2,
          "unit_price": "850.00",
          "subtotal": "1700.00"
        }
      },
      {
        "id": 2,
        "name": "Wireless Mouse",
        "pivot": {
          "quantity": 1,
          "unit_price": "25.50",
          "subtotal": "25.50"
        }
      }
    ]
  }
}
```

**Error Response** (422 Unprocessable Entity):
```json
{
  "success": false,
  "message": "Insufficient stock for product: Laptop HP ProBook. Available: 1"
}
```

> [!IMPORTANT]
> **Stock Management**: When a sale is created, the stock is automatically deducted for each product. The total is calculated based on current product prices.

---

### Get Sale

Retrieve details of a specific sale.

**Endpoint**: `GET /sales/{id}`

**Headers**:
```
Authorization: Bearer {token}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": "9d4e8c12-3f45-4a1b-8e9d-1234567890ab",
    "user_id": 1,
    "total": "1725.50",
    "status": "completed",
    "created_at": "2026-02-06T10:00:00.000000Z",
    "updated_at": "2026-02-06T10:00:00.000000Z",
    "deleted_at": null,
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "products": [
      {
        "id": 1,
        "name": "Laptop HP ProBook",
        "description": "15-inch business laptop",
        "price": "850.00",
        "stock": 13,
        "pivot": {
          "sale_id": "9d4e8c12-3f45-4a1b-8e9d-1234567890ab",
          "product_id": 1,
          "quantity": 2,
          "unit_price": "850.00",
          "subtotal": "1700.00"
        }
      }
    ]
  }
}
```

**Error Response** (403 Forbidden):
```json
{
  "success": false,
  "message": "Unauthorized access"
}
```

> [!NOTE]
> Users can only view their own sales.

---

### Update Sale

Update the status of a sale.

**Endpoint**: `PUT /sales/{id}`

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body**:
```json
{
  "status": "completed"
}
```

**Validation Rules**:
- `status`: required, must be one of: pending, completed, cancelled

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Sale updated successfully",
  "data": {
    "id": "9d4e8c12-3f45-4a1b-8e9d-1234567890ab",
    "user_id": 1,
    "total": "1725.50",
    "status": "completed",
    "created_at": "2026-02-06T10:00:00.000000Z",
    "updated_at": "2026-02-06T11:00:00.000000Z",
    "user": {...},
    "products": [...]
  }
}
```

> [!NOTE]
> Only the status can be updated. Product items cannot be modified after creation.

---

### Delete Sale

Soft delete a sale and restore product stock.

**Endpoint**: `DELETE /sales/{id}`

**Headers**:
```
Authorization: Bearer {token}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Sale deleted successfully"
}
```

> [!IMPORTANT]
> **Stock Restoration**: When a sale is deleted, the stock is automatically restored for all products in that sale.

---

## Sync API

Synchronize offline sales with conflict resolution.

### Sync Sales

Batch synchronize multiple sales created offline.

**Endpoint**: `POST /sync/sales`

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body**:
```json
{
  "sales": [
    {
      "id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
      "products": [
        {
          "product_id": 1,
          "quantity": 2
        }
      ],
      "status": "completed",
      "created_at_client": "2026-02-06T09:30:00.000000Z"
    },
    {
      "id": "b2c3d4e5-f6a7-8901-bcde-f12345678901",
      "products": [
        {
          "product_id": 2,
          "quantity": 5
        }
      ],
      "status": "pending",
      "created_at_client": "2026-02-06T09:45:00.000000Z"
    }
  ]
}
```

**Validation Rules**:
- `sales`: required, array, minimum 1 item
- `sales.*.id`: required, valid UUID
- `sales.*.products`: required, array, minimum 1 item
- `sales.*.products.*.product_id`: required, must exist in products table
- `sales.*.products.*.quantity`: required, integer, minimum 1
- `sales.*.status`: optional, must be one of: pending, completed, cancelled
- `sales.*.created_at_client`: optional, valid date

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Processed 2 sales",
  "results": {
    "success": [
      {
        "id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
        "action": "created",
        "total": "1700.00"
      }
    ],
    "failed": [
      {
        "id": "b2c3d4e5-f6a7-8901-bcde-f12345678901",
        "reason": "Insufficient stock for product: Wireless Mouse. Available: 3, Requested: 5"
      }
    ],
    "skipped": []
  },
  "summary": {
    "total": 2,
    "synced": 1,
    "failed": 1,
    "skipped": 0
  }
}
```

### Conflict Resolution Strategy

The sync API uses a **last-write-wins** strategy based on timestamps:

1. **New Sale (UUID doesn't exist)**:
   - Sale is created with the provided UUID
   - Stock is deducted
   - Marked as "created" in results

2. **Existing Sale (UUID exists)**:
   - Compare `created_at_client` with server's `created_at`
   - If client timestamp is **newer**: Update sale (last-write-wins)
     - Restore stock from old sale
     - Apply new sale data
     - Deduct new stock
     - Marked as "updated" in results
   - If client timestamp is **older or equal**: Skip
     - Marked as "skipped" in results

3. **Ownership Check**:
   - Sales can only be synced by their owner
   - Attempting to sync another user's sale results in failure

4. **Stock Validation**:
   - Stock availability is checked for all products
   - If insufficient stock, the entire sale fails
   - Transaction is rolled back

### Sync Best Practices

> [!TIP]
> **Mobile App Implementation**
> 
> 1. **Generate UUIDs client-side**: Use UUID v4 for all offline sales
> 2. **Store client timestamp**: Save `created_at_client` when sale is created offline
> 3. **Batch sync**: Send multiple sales in one request to reduce network calls
> 4. **Handle partial failures**: Process the results array to update local database
> 5. **Retry failed sales**: Allow users to retry failed syncs after resolving issues
> 6. **Update local status**: Mark successfully synced sales as synced in local DB

**Example Mobile Flow**:
```javascript
// 1. Create sale offline
const offlineSale = {
  id: generateUUID(),
  products: [...],
  status: 'pending',
  created_at_client: new Date().toISOString(),
  synced: false
};
await localDB.sales.insert(offlineSale);

// 2. When online, sync all unsynced sales
const unsyncedSales = await localDB.sales.where('synced').equals(false).toArray();
const response = await api.post('/sync/sales', { sales: unsyncedSales });

// 3. Process results
response.results.success.forEach(sale => {
  localDB.sales.update(sale.id, { synced: true });
});

response.results.failed.forEach(sale => {
  // Show error to user or mark for retry
  console.error(`Failed to sync ${sale.id}: ${sale.reason}`);
});
```

---

## Error Handling

### Standard Error Responses

**Validation Error (422 Unprocessable Entity)**:
```json
{
  "message": "The name field is required. (and 1 more error)",
  "errors": {
    "name": ["The name field is required."],
    "price": ["The price must be at least 0."]
  }
}
```

**Unauthenticated (401 Unauthorized)**:
```json
{
  "message": "Unauthenticated."
}
```

**Not Found (404 Not Found)**:
```json
{
  "message": "No query results for model [App\\Models\\Product] 999"
}
```

**Server Error (500 Internal Server Error)**:
```json
{
  "success": false,
  "message": "Failed to create sale: Database connection error"
}
```

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Request successful |
| 201 | Created - Resource created successfully |
| 401 | Unauthorized - Missing or invalid authentication token |
| 403 | Forbidden - Authenticated but not authorized for this resource |
| 404 | Not Found - Resource doesn't exist |
| 422 | Unprocessable Entity - Validation failed |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error - Server-side error |

---

## Additional Notes

### Database Transactions

All operations that modify multiple records (creating sales, syncing, deleting sales) use database transactions to ensure data consistency. If any part of the operation fails, all changes are rolled back.

### Soft Deletes

Sales use soft deletes, meaning deleted sales are not permanently removed from the database. They are marked with a `deleted_at` timestamp and excluded from normal queries.

### UUID Format

All sale IDs use UUID v4 format:
```
9d4e8c12-3f45-4a1b-8e9d-1234567890ab
```

### Pagination

All list endpoints support pagination with the following parameters:
- `per_page`: Items per page (default: 15, max: 100)
- `page`: Page number (default: 1)

Pagination metadata is included in all list responses.

---

**API Version**: 1.0  
**Last Updated**: 2026-02-06  
**Laravel Version**: 12.x  
**Authentication**: Laravel Sanctum
