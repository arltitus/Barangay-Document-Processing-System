# Barangay Document Processing System API Documentation

## Authentication

### Obtain API Key
Contact system administrator to obtain an API key. This key must be included in all requests via the `X-API-KEY` header.

### Authentication Endpoints

#### Login
```
POST /api/auth/login
```
Request:
```json
{
    "email": "user@example.com",
    "password": "password"
}
```
Response:
```json
{
    "success": true,
    "data": {
        "token": "JWT_TOKEN_HERE",
        "expires_in": 3600
    }
}
```

#### Logout
```
POST /api/auth/logout
```
Headers:
- `Authorization: Bearer JWT_TOKEN`

Response:
```json
{
    "success": true,
    "data": {
        "message": "Successfully logged out"
    }
}
```

## Document Management

### List Documents
```
GET /api/documents
```
Query Parameters:
- `status` - Filter by status (pending, approved, completed, cancelled)
- `type_id` - Filter by document type
- `page` - Page number (default: 1)
- `limit` - Results per page (default: 20)

### Request Document
```
POST /api/documents/request
```
Request:
```json
{
    "document_type_id": 1,
    "purpose": "Employment requirement",
    "appointment_date": "2025-08-15",
    "appointment_time": "09:00:00"
}
```

### Upload Document
```
POST /api/documents/upload
```
Form Data:
- `request_id` - Request ID
- `document` - File upload (PDF, PNG, JPG)

### Cancel Request
```
DELETE /api/documents/{id}
```

## User Management

### Get Profile
```
GET /api/users/profile
```

### Update Profile
```
PUT /api/users/profile
```
Request:
```json
{
    "full_name": "John Doe",
    "address": "123 Main St",
    "phone": "1234567890"
}
```

## Analytics

### Dashboard Statistics
```
GET /api/analytics/dashboard
```

### Audit Logs
```
GET /api/analytics/audit
```
Query Parameters:
- `action` - Filter by action type
- `user_id` - Filter by user
- `date_from` - Start date
- `date_to` - End date
- `page` - Page number

### Request Analytics
```
GET /api/analytics/requests
```
Query Parameters:
- `zone_id` - Filter by zone
- `document_type_id` - Filter by document type
- `status` - Filter by status
- `date_range` - Time period (day, week, month, year)

## Error Responses

All endpoints may return the following error responses:

### 400 Bad Request
```json
{
    "success": false,
    "error": "Error message here"
}
```

### 401 Unauthorized
```json
{
    "success": false,
    "error": "Invalid or expired token"
}
```

### 403 Forbidden
```json
{
    "success": false,
    "error": "Insufficient permissions"
}
```

### 429 Too Many Requests
```json
{
    "success": false,
    "error": "Rate limit exceeded",
    "reset_at": "2025-08-08T12:00:00Z"
}
```

## Rate Limiting

- Default rate limit: 100 requests per minute per API key
- Custom limits can be configured for specific API keys
- Rate limit headers are included in all responses:
  - `X-RateLimit-Limit`
  - `X-RateLimit-Remaining`
  - `X-RateLimit-Reset`

## Security Requirements

1. All requests must include:
   - Valid API key in `X-API-KEY` header
   - JWT token in `Authorization: Bearer` header (except login)

2. SSL/TLS is required for all API calls

3. Request size limits:
   - JSON payload: 10MB
   - File uploads: 5MB

4. API keys and tokens:
   - API keys never expire but can be revoked
   - JWT tokens expire after 1 hour
   - Refresh tokens are not supported - need to login again

5. IP-based rate limiting is also enforced

## Webhook Support

### Register Webhook
```
POST /api/webhooks/register
```
Request:
```json
{
    "url": "https://your-domain.com/webhook",
    "events": ["document.approved", "document.completed"],
    "secret": "your_webhook_secret"
}
```

### Event Types
- `document.requested`
- `document.approved`
- `document.cancelled`
- `document.completed`
- `user.verified`

### Webhook Payload
```json
{
    "event": "document.approved",
    "timestamp": "2025-08-08T10:00:00Z",
    "data": {
        "request_id": 123,
        "status": "approved",
        "user_id": 456
    }
}
