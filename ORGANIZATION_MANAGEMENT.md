# Organization Management Feature

## Overview
This document describes the organization management feature that allows users to register with organization creation, and enables super-admins to manage organizations and their users.

## New Endpoints

### 1. Register User with Organization

**POST** `/api/auth/register-with-organization`

Allows a user to register and optionally create an organization at the same time. If `is_organization` is true, the user becomes an admin of the newly created organization.

#### Request Body

```json
{
  "email": "user@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "firstname": "John",
  "lastname": "Doe",
  "gender": "male",
  "date_of_birth": "1990-01-01", // optional
  "phone": "+1234567890", // optional
  "avatar": "https://example.com/avatar.jpg", // optional
  "is_organization": true,
  "organization_name": "My Organization", // required if is_organization is true
  "organization_description": "A great organization" // required if is_organization is true
}
```

#### Response (201 Created)

```json
{
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "firstname": "John",
    "lastname": "Doe",
    "gender": "male",
    "date_of_birth": "1990-01-01",
    "phone": "+1234567890",
    "avatar": "https://example.com/avatar.jpg",
    "organization_id": "org-uuid"
  },
  "organization": {
    "id": "org-uuid",
    "name": "My Organization",
    "description": "A great organization",
    "created_at": "2026-02-13T12:00:00.000000Z",
    "updated_at": "2026-02-13T12:00:00.000000Z"
  },
  "token": "1|abcdef123456...",
  "message": "User registered successfully"
}
```

#### Notes
- When `is_organization` is `false`, the `organization` field in the response will be `null`
- Users registered with `is_organization: true` are automatically assigned the `admin` role
- Users registered with `is_organization: false` are automatically assigned the `user` role
- The `organization_name` must be unique across all organizations
- Both `organization_name` and `organization_description` are required when `is_organization` is `true`

## Existing Endpoints for Organization Management

### 2. Create Organization (Super-Admin/Authenticated User)

**POST** `/api/organizations`

Allows authenticated users to create a new organization.

**Authentication Required:** Yes

#### Request Body

```json
{
  "name": "Organization Name",
  "description": "Organization description"
}
```

#### Response (201 Created)

```json
{
  "id": "uuid",
  "name": "Organization Name",
  "description": "Organization description",
  "created_at": "2026-02-13T12:00:00.000000Z",
  "updated_at": "2026-02-13T12:00:00.000000Z"
}
```

### 3. Get Organization Details

**GET** `/api/organizations/{organization}`

Retrieve organization details including its users.

**Authentication Required:** Yes

#### Response (200 OK)

```json
{
  "id": "uuid",
  "name": "Organization Name",
  "description": "Organization description",
  "users": [
    {
      "id": "uuid",
      "email": "user@example.com",
      "firstname": "John",
      "lastname": "Doe"
    }
  ],
  "created_at": "2026-02-13T12:00:00.000000Z",
  "updated_at": "2026-02-13T12:00:00.000000Z"
}
```

### 4. Create Agent within Organization

**POST** `/api/organizations/{organization}/agents`

Create an agent user within a specific organization. Only super-admins or organization admins can create agents.

**Authentication Required:** Yes
**Permissions Required:** Super-admin OR admin of the organization

#### Request Body

```json
{
  "email": "agent@example.com",
  "firstname": "Jane",
  "lastname": "Smith",
  "gender": "female",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+1234567890", // optional
  "avatar": "https://example.com/avatar.jpg" // optional
}
```

#### Response (201 Created)

```json
{
  "agent": {
    "id": "uuid",
    "email": "agent@example.com",
    "firstname": "Jane",
    "lastname": "Smith",
    "organization_id": "org-uuid"
  }
}
```

#### Authorization Rules
- **Super-admins**: Can create agents in any organization
- **Organization admins**: Can create agents only in their own organization
- **Regular users**: Cannot create agents (403 Forbidden)

### 5. Assign Admin to Organization

**POST** `/api/organizations/{organization}/admins`

Assign an existing user as admin to an organization or create a new admin user.

**Authentication Required:** Yes
**Permissions Required:** Super-admin OR admin of the organization

#### Request Body (Existing User)

```json
{
  "user_id": "user-uuid"
}
```

#### Request Body (New User)

```json
{
  "email": "newadmin@example.com",
  "firstname": "Admin",
  "lastname": "User",
  "phone": "+1234567890", // optional
  "avatar": "https://example.com/avatar.jpg" // optional
}
```

#### Response (201 Created)

```json
{
  "admin": {
    "id": "uuid",
    "email": "admin@example.com",
    "firstname": "Admin",
    "lastname": "User",
    "organization_id": "org-uuid"
  }
}
```

## User Roles

The system supports four user roles:

1. **user**: Regular user without special permissions
2. **agent**: User who can manage posts, categories, and tags within an organization
3. **admin**: Administrator of an organization, can create agents and manage the organization
4. **super-admin**: System administrator with full access to all organizations and features

## Role Assignment Logic

- Users registered via `/api/auth/register` → `user` role
- Users registered via `/api/auth/register-with-organization` with `is_organization: false` → `user` role
- Users registered via `/api/auth/register-with-organization` with `is_organization: true` → `admin` role
- Users created via `/api/organizations/{organization}/agents` → `agent` role
- Users assigned via `/api/organizations/{organization}/admins` → `admin` role

## Database Schema

### Users Table
- `id` (uuid, primary key)
- `email` (string, unique)
- `password` (string, hashed)
- `firstname` (string)
- `lastname` (string)
- `gender` (enum: male, female)
- `date_of_birth` (date, nullable)
- `phone` (string, nullable)
- `avatar` (string, nullable)
- `status` (enum)
- `organization_id` (uuid, foreign key, nullable)
- `provider` (string, nullable)
- `provider_id` (string, nullable)
- timestamps

### Organizations Table
- `id` (uuid, primary key)
- `name` (string, unique)
- `description` (text, nullable)
- timestamps

### Relationships
- User `belongsTo` Organization
- Organization `hasMany` Users

## Examples

### Example 1: Register as Regular User

```bash
curl -X POST http://localhost:8000/api/auth/register-with-organization \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "firstname": "John",
    "lastname": "Doe",
    "gender": "male",
    "is_organization": false
  }'
```

### Example 2: Register with Organization Creation

```bash
curl -X POST http://localhost:8000/api/auth/register-with-organization \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@company.com",
    "password": "password123",
    "password_confirmation": "password123",
    "firstname": "Jane",
    "lastname": "Smith",
    "gender": "female",
    "is_organization": true,
    "organization_name": "Tech Company Inc",
    "organization_description": "A leading technology company"
  }'
```

### Example 3: Super-admin Creates Organization

```bash
curl -X POST http://localhost:8000/api/organizations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {super-admin-token}" \
  -d '{
    "name": "New Organization",
    "description": "Created by super admin"
  }'
```

### Example 4: Admin Creates Agent in Their Organization

```bash
curl -X POST http://localhost:8000/api/organizations/{org-id}/agents \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {admin-token}" \
  -d '{
    "email": "agent@company.com",
    "firstname": "Bob",
    "lastname": "Agent",
    "gender": "male",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

## Validation Rules

### Registration with Organization
- `email`: required, email format, max 255 chars, unique
- `password`: required, string, min 8 chars, must be confirmed
- `firstname`: required, string, max 100 chars
- `lastname`: required, string, max 100 chars
- `gender`: required, enum (male, female)
- `date_of_birth`: optional, valid date
- `phone`: optional, string, max 30 chars
- `avatar`: optional, valid URL
- `is_organization`: required, boolean
- `organization_name`: required if `is_organization` is true, string, max 255 chars, unique
- `organization_description`: required if `is_organization` is true, string

## Error Responses

### 422 Validation Error

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["L'adresse email est déjà utilisée."],
    "organization_name": ["Ce nom d'organisation est déjà utilisé."]
  }
}
```

### 401 Unauthorized

```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden

```json
{
  "message": "Not allowed to create agents for this organization"
}
```

## Testing

All features are covered by comprehensive tests:

1. **RegisterWithOrganizationTest.php**: 13 tests covering user registration with/without organization
2. **SuperAdminOrganizationTest.php**: 6 tests covering super-admin and organization admin permissions
3. **OrganizationControllerTest.php**: 17 tests covering organization CRUD and user management
4. **AuthControllerTest.php**: 18 tests covering standard authentication

Total: 54 tests ensuring the feature works correctly.
