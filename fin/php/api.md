# SwiftCart API Documentation

## Base URL
```
/api/{endpoint}.php?action={action}
```

## Authentication
All authenticated endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer {token}
```

---

## Auth Endpoints

### Register
```
POST /api/auth.php?action=register
```
**Body:**
```json
{
    "name": "string",
    "email": "string",
    "phone": "string",
    "password": "string"
}
```

### Login
```
POST /api/auth.php?action=login
```
**Body:**
```json
{
    "email": "string",
    "password": "string"
}
```
**Response:**
```json
{
    "success": true,
    "data": {
        "user": {...},
        "token": "jwt_token"
    }
}
```

### Get Profile
```
GET /api/auth.php?action=profile
```
🔒 Requires Auth

### Update Profile
```
PUT /api/auth.php?action=update
```
🔒 Requires Auth

---

## Products Endpoints

### List Products
```
GET /api/products.php?action=list
```
**Query Parameters:**
- `category` - Filter by category ID
- `search` - Search query
- `sort` - newest, price_asc, price_desc, popular
- `featured` - 1 for featured only
- `page` - Page number
- `limit` - Items per page (default: 12)

### Get Product
```
GET /api/products.php?action=get&id={id}
```

### Search Products
```
GET /api/products.php?action=search&q={query}
```

---

## Categories Endpoints

### List Categories
```
GET /api/categories.php?action=list
```

### Get Category
```
GET /api/categories.php?action=get&id={id}
```

---

## Cart Endpoints
🔒 All cart endpoints require authentication

### Get Cart
```
GET /api/cart.php?action=list
```

### Add to Cart
```
POST /api/cart.php?action=add
```
**Body:**
```json
{
    "product_id": 1,
    "quantity": 1,
    "variant_id": null
}
```

### Update Cart Item
```
PUT /api/cart.php?action=update
```
**Body:**
```json
{
    "item_id": 1,
    "quantity": 2
}
```

### Remove from Cart
```
DELETE /api/cart.php?action=remove&item_id={id}
```

### Clear Cart
```
DELETE /api/cart.php?action=clear
```

### Calculate Totals
```
GET /api/cart.php?action=totals&coupon={code}
```

---

## Orders Endpoints
🔒 All order endpoints require authentication

### List Orders
```
GET /api/orders.php?action=list&page={page}
```

### Get Order
```
GET /api/orders.php?action=get&id={id}
```

### Create Order
```
POST /api/orders.php?action=create
```
**Body:**
```json
{
    "address_id": 1,
    "payment_method": "cod",
    "coupon_code": "SAVE10",
    "notes": "optional notes"
}
```

### Cancel Order
```
POST /api/orders.php?action=cancel&id={id}
```

---

## Reviews Endpoints

### List Reviews
```
GET /api/reviews.php?action=list&product_id={id}
```

### Get Stats
```
GET /api/reviews.php?action=stats&product_id={id}
```

### Create Review
```
POST /api/reviews.php?action=create
```
🔒 Requires Auth

**Body:**
```json
{
    "product_id": 1,
    "rating": 5,
    "title": "Great product",
    "comment": "Review text"
}
```

---

## Response Format

### Success Response
```json
{
    "success": true,
    "message": "Optional message",
    "data": {...}
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error description",
    "errors": []
}
```

### Paginated Response
```json
{
    "success": true,
    "data": [...],
    "pagination": {
        "total": 100,
        "per_page": 12,
        "current_page": 1,
        "last_page": 9
    }
}
```

---

## Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Too Many Requests
- `500` - Server Error
