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

## Settings

### Get IP Whitelist Enforcement Status
```http
GET /api/v1/settings/ip-whitelist-status
Authorization: Bearer {token}
```

**Description:** Returns whether IP whitelist enforcement is enabled globally. Both admin and employee users can access this endpoint.

**Response:**
```json
{
  "success": true,
  "message": "IP whitelist enforcement status retrieved successfully",
  "data": {
    "enforce_ip_whitelist": true,
    "enabled": true
  }
}
```

### Get Location Guard Enforcement Status
```http
GET /api/v1/settings/location-guard-status
Authorization: Bearer {token}
```

**Description:** Returns whether office location (location guard) enforcement is enabled globally. Includes office coordinates and radius if configured.

**Response:**
```json
{
  "success": true,
  "message": "Location guard enforcement status retrieved successfully",
  "data": {
    "enforce_office_location": true,
    "enabled": true,
    "office_configured": true,
    "office_latitude": 40.7128,
    "office_longitude": -74.0060,
    "office_radius_meters": 50
  }
}
```

### Get Combined Attendance Settings
```http
GET /api/v1/settings/attendance
Authorization: Bearer {token}
```

**Description:** Returns combined settings for attendance including IP whitelist and location guard status. For employee tokens, also includes employee-specific geolocation settings.

**Admin Response:**
```json
{
  "success": true,
  "message": "Attendance settings retrieved successfully",
  "data": {
    "ip_whitelist": {
      "enforce_ip_whitelist": true,
      "enabled": true
    },
    "location_guard": {
      "enforce_office_location": true,
      "enabled": true,
      "office_configured": true,
      "office_latitude": 40.7128,
      "office_longitude": -74.0060,
      "office_radius_meters": 50
    }
  }
}
```

**Employee Response:**
```json
{
  "success": true,
  "message": "Attendance settings retrieved successfully",
  "data": {
    "ip_whitelist": {
      "enforce_ip_whitelist": true,
      "enabled": true
    },
    "location_guard": {
      "enforce_office_location": true,
      "enabled": true,
      "office_configured": true,
      "office_latitude": 40.7128,
      "office_longitude": -74.0060,
      "office_radius_meters": 50
    },
    "employee": {
      "geolocation_mode": "required",
      "geolocation_required": true,
      "enforces_office_radius": true,
      "uses_whitelist_override": false,
      "has_ip_whitelist": false,
      "ip_whitelists_count": 0
    }
  }
}
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

### Get Current Attendance Status
```http
GET /api/v1/attendance/status
Authorization: Bearer {token}
```

**Description:** Get the current attendance status for the authenticated employee. Includes today's attendance, check-in/check-out status, and any pending checkouts from previous days.

**Query Parameters (Admin Only):**
- `employee_user_id` (optional): Check status for a specific employee (admin only)

**Response:**
```json
{
  "success": true,
  "message": "Current attendance status retrieved successfully",
  "data": {
    "today_date": "2024-01-15",
    "checked_in_today": true,
    "checked_out_today": false,
    "can_check_in": false,
    "can_check_out": true,
    "has_pending_checkout": false,
    "today_attendance": {
      "id": 123,
      "employee_user_id": 5,
      "attendance_date": "2024-01-15",
      "check_in": "2024-01-15T09:00:00Z",
      "check_in_latitude": 40.7128,
      "check_in_longitude": -74.0060,
      "check_in_distance_meters": 15.5,
      "check_out": null,
      "work_duration": null,
      "has_checked_in": true,
      "has_checked_out": false
    }
  }
}
```

**Response (with pending checkout):**
```json
{
  "success": true,
  "message": "Current attendance status retrieved successfully",
  "data": {
    "today_date": "2024-01-15",
    "checked_in_today": false,
    "checked_out_today": false,
    "can_check_in": false,
    "can_check_out": false,
    "has_pending_checkout": true,
    "pending_attendance": {
      "id": 122,
      "date": "2024-01-14",
      "check_in": "2024-01-14 09:00:00"
    }
  }
}
```

### Get Attendance Statistics (Admin Only)
```http
GET /api/v1/attendance/statistics?date_from=2024-01-01&date_to=2024-01-31
Authorization: Bearer {admin_token}
```

**Important Notes on Attendance APIs:**

> [!IMPORTANT]
> **IP Whitelist Enforcement**: When IP whitelist enforcement is enabled globally (`enforce_ip_whitelist = true`), employees with IP whitelists configured must check in/out from whitelisted IPs. If enforcement is disabled, IP whitelists are ignored.

> [!IMPORTANT]
> **Location Guard Enforcement**: When office location enforcement is enabled globally (`enforce_office_location = true`), employees with geolocation required must be within the configured office radius. If enforcement is disabled, location checks are bypassed.

> [!NOTE]
> **Geolocation Modes**:
> - `disabled`: No geolocation required for this employee
> - `required`: Employee must be within office radius to check in/out
> - `required_with_whitelist`: Employee must be within office radius OR connected from a whitelisted IP

> [!WARNING]
> **Edge Cases Handled**:
> - **Already Checked In**: Returns 400 error if attempting to check in when already checked in today
> - **Already Checked Out**: Returns 400 error if attempting to check out when already checked out
> - **Pending Checkout**: Prevents new check-in if there's an unclosed attendance from a previous day
> - **IP Not Whitelisted**: Returns 403 error if IP whitelist enforcement is enabled and current IP is not whitelisted
> - **Out of Range**: Returns 403 error if employee is outside office radius (unless IP whitelisted in whitelist mode)
> - **Office Not Configured**: Returns 400 error if geolocation is required but office location is not set
> - **Missing Coordinates**: Returns 422 validation error if geolocation is required but coordinates are not provided

**Error Response Examples:**

IP Not Whitelisted:
```json
{
  "success": false,
  "message": "Your current network is not whitelisted for attendance. Please connect using an approved IP address."
}
```

Out of Office Radius:
```json
{
  "success": false,
  "message": "You are too far from the office. You must be within 50 meters to check in. Current distance: 125.50 meters.",
  "distance": 125.5,
  "required_distance": 50
}
```

Pending Checkout from Previous Day:
```json
{
  "success": false,
  "message": "You still have an open attendance from Jan 14, 2024. Please check out first or request an attendance fix."
}
```

---

## Office Schedule

### Get Office Schedule
```http
GET /api/v1/office-schedule
Authorization: Bearer {token}
```

**Description:** Get the office schedule. Admin users get their own schedule, employee users get their admin's schedule (read-only).

**Response:**
```json
{
  "success": true,
  "message": "Office schedule retrieved successfully",
  "data": {
    "schedule": {
      "id": 1,
      "start_time": "09:00",
      "end_time": "17:00",
      "working_days": ["monday", "tuesday", "wednesday", "thursday", "friday"],
      "timezone": "America/New_York",
      "created_at": "2024-01-15T10:00:00Z",
      "updated_at": "2024-01-15T10:00:00Z"
    },
    "has_schedule": true
  }
}
```

### Update Office Schedule (Admin Only)
```http
PUT /api/v1/office-schedule
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "start_time": "09:00",
  "end_time": "17:00",
  "working_days": ["monday", "tuesday", "wednesday", "thursday", "friday"],
  "timezone": "America/New_York"
}
```

**Validation:**
- `start_time`: Required, format H:i (e.g., "09:00")
- `end_time`: Required, format H:i, must be after start_time
- `working_days`: Required array, minimum 1 day, valid values: monday, tuesday, wednesday, thursday, friday, saturday, sunday
- `timezone`: Optional, valid timezone identifier

---

## Office Closures

### List Office Closures
```http
GET /api/v1/office-closures?active_only=true&page=1&per_page=15
Authorization: Bearer {token}
```

**Query Parameters:**
- `active_only` (optional): Show only active/upcoming closures
- `date_from` (optional): Filter from date
- `date_to` (optional): Filter to date
- `page` (optional): Page number
- `per_page` (optional): Items per page

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "start_date": "2024-12-25",
      "end_date": "2024-12-26",
      "reason": "Christmas Holiday",
      "is_single_day": false,
      "is_active": false,
      "created_at": "2024-01-15T10:00:00Z",
      "updated_at": "2024-01-15T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 5,
    "per_page": 15
  }
}
```

### Create Office Closure (Admin Only)
```http
POST /api/v1/office-closures
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "start_date": "2024-12-25",
  "end_date": "2024-12-26",
  "reason": "Christmas Holiday"
}
```

**Validation:**
- `start_date`: Required, valid date
- `end_date`: Optional, valid date, must be >= start_date
- `reason`: Optional, max 255 characters

### Get Office Closure
```http
GET /api/v1/office-closures/{id}
Authorization: Bearer {token}
```

### Delete Office Closure (Admin Only)
```http
DELETE /api/v1/office-closures/{id}
Authorization: Bearer {admin_token}
```

---

## Attendance Logs (Admin Only)

### List Attendance Logs
```http
GET /api/v1/attendance-logs?employee_user_id=5&action=check_in_failed&failed_only=true
Authorization: Bearer {admin_token}
```

**Query Parameters:**
- `employee_user_id` (optional): Filter by employee
- `action` (optional): Filter by action (check_in_success, check_in_failed, check_out_success, check_out_failed)
- `failure_reason` (optional): Filter by failure reason
- `date_from` (optional): Filter from date
- `date_to` (optional): Filter to date
- `failed_only` (optional): Show only failed attempts
- `successful_only` (optional): Show only successful attempts
- `sort` (optional): Sort field
- `direction` (optional): Sort direction (asc, desc)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "employee_user_id": 5,
      "employee_name": "John Doe",
      "attendance_id": 45,
      "action": "check_in_failed",
      "failure_reason": "out_of_range",
      "latitude": 40.7128,
      "longitude": -74.0060,
      "distance_from_office": 125.50,
      "ip_address": "192.168.1.1",
      "ip_address_v4": "192.168.1.1",
      "ip_address_v6": null,
      "user_agent": "Mozilla/5.0...",
      "device_type": "Mobile",
      "browser": "Chrome 120.0",
      "os": "Android 14",
      "additional_info": "{\"distance_meters\":125.5}",
      "attempted_at": "2024-01-15T09:00:00Z",
      "created_at": "2024-01-15T09:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 50,
    "per_page": 15
  }
}
```

### Get Attendance Log
```http
GET /api/v1/attendance-logs/{id}
Authorization: Bearer {admin_token}
```

### Cleanup Old Logs (Admin Only)
```http
POST /api/v1/attendance-logs/cleanup
Authorization: Bearer {admin_token}
```

**Description:** Removes attendance logs older than the retention period (default: 90 days).

---

## Activity Logs (Admin Only)

### List Activity Logs
```http
GET /api/v1/activity-logs?employee_user_id=5&category=attendance&search=check
Authorization: Bearer {admin_token}
```

**Query Parameters:**
- `employee_user_id` (optional): Filter by employee
- `category` (optional): Filter by category
- `action` (optional): Filter by action
- `request_method` (optional): Filter by HTTP method
- `device_type` (optional): Filter by device type
- `response_status` (optional): Filter by HTTP status code
- `ip_address` (optional): Filter by IP address (partial match)
- `search` (optional): Search across summary, description, action, path, etc.
- `date_from` (optional): Filter from date
- `date_to` (optional): Filter to date
- `sort` (optional): Sort field
- `direction` (optional): Sort direction (asc, desc)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 456,
      "employee_user_id": 5,
      "employee_name": "John Doe",
      "category": "attendance",
      "action": "check_in",
      "summary": "Employee checked in successfully",
      "description": "Check-in from mobile app",
      "request_method": "POST",
      "route_name": "attendance.check-in",
      "request_path": "/attendance/check-in",
      "response_status": 200,
      "ip_address": "192.168.1.1",
      "ip_address_v4": "192.168.1.1",
      "ip_address_v6": null,
      "device_type": "Mobile",
      "browser": "Chrome 120.0",
      "os": "Android 14",
      "occurred_at": "2024-01-15T09:00:00Z",
      "created_at": "2024-01-15T09:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 200,
    "per_page": 15
  }
}
```

### Get Activity Log
```http
GET /api/v1/activity-logs/{id}
Authorization: Bearer {admin_token}
```

### Cleanup Old Logs (Admin Only)
```http
POST /api/v1/activity-logs/cleanup
Authorization: Bearer {admin_token}
```

**Description:** Removes activity logs older than the retention period (default: 90 days).

---

## Fix Requests

### List Fix Requests
```http
GET /api/v1/fix-requests?status=pending
Authorization: Bearer {token}
```

**Description:** Employees see their own fix requests, admins see all fix requests for their employees.

**Query Parameters:**
- `status` (optional): Filter by status (pending, approved, rejected)
- `employee_user_id` (optional): Filter by employee (admin only)
- `date_from` (optional): Filter from date
- `date_to` (optional): Filter to date

**Response (Employee):**
```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "employee_user_id": 5,
      "attendance_id": 45,
      "reason": "Forgot to check out yesterday",
      "status": "pending",
      "admin_notes": null,
      "processed_at": null,
      "created_at": "2024-01-15T10:00:00Z",
      "updated_at": "2024-01-15T10:00:00Z",
      "attendance": {
        "id": 45,
        "attendance_date": "2024-01-14",
        "check_in": "2024-01-14T09:00:00Z",
        "check_out": null
      }
    }
  ]
}
```

**Response (Admin):**
```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "employee_user_id": 5,
      "employee_name": "John Doe",
      "attendance_id": 45,
      "reason": "Forgot to check out yesterday",
      "status": "pending",
      "admin_notes": null,
      "processed_at": null,
      "created_at": "2024-01-15T10:00:00Z",
      "updated_at": "2024-01-15T10:00:00Z",
      "attendance": {
        "id": 45,
        "attendance_date": "2024-01-14",
        "check_in": "2024-01-14T09:00:00Z",
        "check_out": null
      }
    }
  ]
}
```

### Create Fix Request (Employee Only)
```http
POST /api/v1/fix-requests
Authorization: Bearer {employee_token}
Content-Type: application/json

{
  "attendance_id": 45,
  "reason": "Forgot to check out yesterday due to emergency"
}
```

**Validation:**
- `attendance_id`: Required, must exist and belong to the employee
- `reason`: Required, max 1000 characters

**Edge Cases:**
- Returns 400 if a pending fix request already exists for the attendance
- Returns 403 if attendance doesn't belong to the employee

### Get Fix Request
```http
GET /api/v1/fix-requests/{id}
Authorization: Bearer {token}
```

### Process Fix Request (Admin Only)
```http
POST /api/v1/fix-requests/{id}/process
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "status": "approved",
  "admin_notes": "Approved. Please remember to check out on time."
}
```

**Validation:**
- `status`: Required, must be "approved" or "rejected"
- `admin_notes`: Optional, max 1000 characters

**Response:**
```json
{
  "success": true,
  "message": "Fix request has been approved successfully",
  "data": {
    "id": 10,
    "status": "approved",
    "admin_notes": "Approved. Please remember to check out on time.",
    "processed_at": "2024-01-15T11:00:00Z",
    "processed_by": {
      "id": 1,
      "name": "Admin User"
    }
  }
}
```

**Edge Cases:**
- Returns 400 if fix request has already been processed
- Returns 404 if fix request doesn't belong to admin's employees

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
