# REST API Documentation

## Base URL
```
http://your-domain.com/api/v1
```

## Authentication

All API endpoints (except login) require authentication using Bearer tokens.

### Login (Admin)
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "1|abc123...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "type": "admin",
      "profile_photo_url": null
    }
  }
}
```

### Login (Employee)
```http
POST /api/v1/auth/employee/login
Content-Type: application/json

{
  "email": "employee@example.com",
  "password": "password"
}
```

### Logout
```http
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

### Get Current User
```http
GET /api/v1/auth/me
Authorization: Bearer {token}
```

### Refresh Token
```http
POST /api/v1/auth/refresh
Authorization: Bearer {token}
```

---

## Employees (Admin Only)

### List Employees
```http
GET /api/v1/employees?page=1&per_page=15&search=john&sort=name&direction=asc
Authorization: Bearer {admin_token}
```

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 15, max: 100)
- `search` (optional): Search in name, email, role, contact
- `sort` (optional): Sort field (id, name, email, role, employment_type, joining_date, salary, created_at)
- `direction` (optional): Sort direction (asc, desc)
- `filter[role]` (optional): Filter by role
- `filter[employment_type]` (optional): Filter by employment type
- `filter[geolocation_mode]` (optional): Filter by geolocation mode

**Response:**
```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 73
  },
  "links": {
    "first": "http://...",
    "last": "http://...",
    "prev": null,
    "next": "http://..."
  }
}
```

### Create Employee
```http
POST /api/v1/employees
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "primary_contact": "+1234567890",
  "role": "Developer",
  "employment_type": "full_time",
  "joining_date": "2024-01-01",
  "salary": 50000,
  "commission_rate": 5,
  "currency_id": 1,
  "geolocation_mode": "required",
  "ip_whitelists": [
    {
      "ip_address": "192.168.1.1",
      "label": "Home"
    }
  ]
}
```

### Get Employee
```http
GET /api/v1/employees/{id}
Authorization: Bearer {admin_token}
```

### Update Employee
```http
PUT /api/v1/employees/{id}
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "name": "John Smith",
  "salary": 55000
}
```

### Delete Employee
```http
DELETE /api/v1/employees/{id}
Authorization: Bearer {admin_token}
```

### Toggle Geolocation
```http
POST /api/v1/employees/{id}/toggle-geolocation
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "geolocation_mode": "required_with_whitelist"
}
```

### Bulk Actions
```http
POST /api/v1/employees/bulk-action
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "action": "delete",
  "employee_ids": [1, 2, 3]
}
```

---

## Attendance

### List Attendance
```http
GET /api/v1/attendance?date_from=2024-01-01&date_to=2024-01-31
Authorization: Bearer {token}
```

**Query Parameters:**
- `date_from` (optional): Filter from date
- `date_to` (optional): Filter to date
- `filter[attendance_date]` (optional): Specific date
- `filter[employee_user_id]` (optional): Filter by employee (admin only)

### Check In
```http
POST /api/v1/attendance/check-in
Authorization: Bearer {employee_token}
Content-Type: application/json

{
  "latitude": 40.7128,
  "longitude": -74.0060
}
```

**Response:**
```json
{
  "success": true,
  "message": "Checked in successfully",
  "data": {
    "id": 123,
    "employee_user_id": 5,
    "attendance_date": "2024-01-15",
    "check_in": "2024-01-15T09:00:00Z",
    "check_in_latitude": 40.7128,
    "check_in_longitude": -74.0060,
    "check_in_distance_meters": 15.5,
    ...
  }
}
```

### Check Out
```http
POST /api/v1/attendance/check-out
Authorization: Bearer {employee_token}
Content-Type: application/json

{
  "latitude": 40.7128,
  "longitude": -74.0060
}
```

### Get Attendance Statistics (Admin Only)
```http
GET /api/v1/attendance/statistics?date_from=2024-01-01&date_to=2024-01-31
Authorization: Bearer {admin_token}
```

---

## Clients

### List Clients
```http
GET /api/v1/clients?search=acme
Authorization: Bearer {token}
```

### Create Client
```http
POST /api/v1/clients
Authorization: Bearer {token}
Content-Type: multipart/form-data

name=Acme Corp
email=contact@acme.com
primary_contact=+1234567890
website=https://acme.com
picture=@/path/to/image.jpg
```

### Get Client
```http
GET /api/v1/clients/{id}
Authorization: Bearer {token}
```

### Update Client
```http
PUT /api/v1/clients/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Acme Corporation",
  "email": "info@acme.com"
}
```

### Delete Client
```http
DELETE /api/v1/clients/{id}
Authorization: Bearer {token}
```

---

## Invoices

### List Invoices
```http
GET /api/v1/invoices?filter[status]=pending&filter[approval_status]=approved
Authorization: Bearer {token}
```

**Query Parameters:**
- `filter[status]` (optional): pending, paid, overdue
- `filter[approval_status]` (optional): pending, approved, rejected
- `filter[client_id]` (optional): Filter by client
- `filter[employee_id]` (optional): Filter by employee
- `due_date_from` (optional): Filter from due date
- `due_date_to` (optional): Filter to due date

### Create Invoice
```http
POST /api/v1/invoices
Authorization: Bearer {token}
Content-Type: application/json

{
  "client_id": 1,
  "employee_id": 2,
  "currency_id": 1,
  "due_date": "2024-02-01",
  "amount": 5000,
  "tax": 500,
  "special_note": "Project milestone 1",
  "milestones": [
    {
      "title": "Design Phase",
      "amount": 2000,
      "due_date": "2024-01-15"
    },
    {
      "title": "Development Phase",
      "amount": 3000,
      "due_date": "2024-02-01"
    }
  ]
}
```

### Get Invoice
```http
GET /api/v1/invoices/{id}
Authorization: Bearer {token}
```

### Update Invoice
```http
PUT /api/v1/invoices/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 5500,
  "tax": 550
}
```

### Delete Invoice
```http
DELETE /api/v1/invoices/{id}
Authorization: Bearer {admin_token}
```

### Approve Invoice (Admin Only)
```http
POST /api/v1/invoices/{id}/approve
Authorization: Bearer {admin_token}
```

### Reject Invoice (Admin Only)
```http
POST /api/v1/invoices/{id}/reject
Authorization: Bearer {admin_token}
```

### Download Invoice PDF
```http
GET /api/v1/invoices/{id}/pdf
Authorization: Bearer {token}
```

---

## Expenses (Admin Only)

### List Expenses
```http
GET /api/v1/expenses?date_from=2024-01-01&date_to=2024-01-31
Authorization: Bearer {admin_token}
```

### Create Expense
```http
POST /api/v1/expenses
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "description": "Office Supplies",
  "currency_id": 1,
  "amount": 250,
  "date": "2024-01-15"
}
```

### Get Expense
```http
GET /api/v1/expenses/{id}
Authorization: Bearer {admin_token}
```

### Update Expense
```http
PUT /api/v1/expenses/{id}
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "amount": 275
}
```

### Delete Expense
```http
DELETE /api/v1/expenses/{id}
Authorization: Bearer {admin_token}
```

---

## Bonuses (Admin Only)

### List Bonuses
```http
GET /api/v1/bonuses
Authorization: Bearer {admin_token}
```

### Create Bonus
```http
POST /api/v1/bonuses
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "employee_id": 1,
  "currency_id": 1,
  "amount": 1000,
  "description": "Performance bonus",
  "date": "2024-01-31",
  "release_type": "with_salary"
}
```

---

## Salary Releases (Admin Only)

### List Salary Releases
```http
GET /api/v1/salary-releases
Authorization: Bearer {admin_token}
```

### Create Salary Release
```http
POST /api/v1/salary-releases
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "employee_id": 1,
  "currency_id": 1,
  "month": 1,
  "year": 2024,
  "basic_salary": 5000,
  "bonus": 500,
  "commission": 200,
  "deductions": 100,
  "net_salary": 5600,
  "release_date": "2024-02-01",
  "notes": "January 2024 salary"
}
```

### Download Salary Release PDF
```http
GET /api/v1/salary-releases/{id}/pdf
Authorization: Bearer {admin_token}
```

---

## Currencies

### List Currencies
```http
GET /api/v1/currencies
Authorization: Bearer {token}
```

### Create Currency (Admin Only)
```http
POST /api/v1/currencies
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "code": "EUR",
  "name": "Euro",
  "symbol": "€",
  "conversion_rate": 0.85,
  "is_active": true
}
```

### Update Currency (Admin Only)
```http
PUT /api/v1/currencies/{id}
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "conversion_rate": 0.87
}
```

### Set Base Currency (Admin Only)
```http
POST /api/v1/currencies/{id}/set-base
Authorization: Bearer {admin_token}
```

### Toggle Currency Active Status (Admin Only)
```http
POST /api/v1/currencies/{id}/toggle-active
Authorization: Bearer {admin_token}
```

### Delete Currency (Admin Only)
```http
DELETE /api/v1/currencies/{id}
Authorization: Bearer {admin_token}
```

---

## Dashboard

### Get Dashboard Statistics
```http
GET /api/v1/dashboard
Authorization: Bearer {token}
```

**Admin Response:**
```json
{
  "success": true,
  "data": {
    "invoices": {
      "total": 150,
      "pending": 25,
      "paid": 120,
      "total_revenue": 500000,
      "monthly_revenue": 45000
    },
    "employees": {
      "total": 25,
      "active": 23
    },
    "expenses": {
      "monthly_total": 12000
    },
    "attendance": {
      "today_records": 20,
      "checked_in_today": 18
    }
  }
}
```

**Employee Response:**
```json
{
  "success": true,
  "data": {
    "attendance": {
      "monthly_records": 22,
      "today_checked_in": true,
      "today_checked_out": false
    },
    "invoices": {
      "total": 15,
      "pending_approval": 3
    }
  }
}
```

---

## Error Responses

All error responses follow this format:

```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### HTTP Status Codes
- `200` - Success
- `201` - Created
- `204` - No Content
- `400` - Bad Request
- `401` - Unauthorized (invalid or missing token)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

---

## Rate Limiting

API requests are rate-limited to **60 requests per minute** per authenticated user.

When rate limit is exceeded, you'll receive a `429 Too Many Requests` response.

---

## Pagination

All list endpoints support pagination with the following parameters:
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15, max: 100)

Paginated responses include:
- `data` - Array of items
- `meta` - Pagination metadata (current_page, total, per_page, etc.)
- `links` - Navigation links (first, last, prev, next)

---

## Filtering

Use the `filter` query parameter for filtering:
```
GET /api/v1/employees?filter[role]=developer&filter[employment_type]=full_time
```

---

## Sorting

Use `sort` and `direction` parameters:
```
GET /api/v1/employees?sort=name&direction=asc
```

---

## Searching

Use the `search` parameter for full-text search:
```
GET /api/v1/clients?search=acme
```

---

## Best Practices

1. **Always include the Authorization header** with your Bearer token
2. **Handle token expiration** - Refresh tokens when needed or re-authenticate
3. **Use pagination** for large datasets to improve performance
4. **Implement retry logic** for failed requests with exponential backoff
5. **Cache responses** where appropriate to reduce API calls
6. **Validate data** on the client side before sending to reduce validation errors
7. **Use HTTPS** in production for secure communication
8. **Store tokens securely** - Never expose tokens in URLs or logs
