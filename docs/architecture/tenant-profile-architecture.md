# Tenant Profile Update - Architecture Documentation

## System Overview

The tenant profile update feature is a self-service capability that allows tenants to manage their own profile information within the hierarchical user management system.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         Browser (Client)                         │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  Tenant Profile View (show.blade.php)                      │ │
│  │  ┌──────────────┐  ┌──────────────┐  ┌─────────────────┐ │ │
│  │  │ Profile Info │  │ Update Form  │  │ Password Change │ │ │
│  │  └──────────────┘  └──────────────┘  └─────────────────┘ │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ HTTP Request (GET/PUT)
                              │ + CSRF Token
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Laravel Application                         │
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                    Middleware Stack                         │ │
│  │  ┌──────┐  ┌──────┐  ┌──────────┐  ┌─────────────────┐  │ │
│  │  │ web  │→│ auth │→│role:tenant│→│subscription.check│  │ │
│  │  └──────┘  └──────┘  └──────────┘  └─────────────────┘  │ │
│  │                           │                                 │ │
│  │                           ▼                                 │ │
│  │                  ┌─────────────────┐                       │ │
│  │                  │hierarchical.    │                       │ │
│  │                  │access           │                       │ │
│  │                  └─────────────────┘                       │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                   │
│                              ▼                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │              ProfileController                              │ │
│  │  ┌──────────────────┐         ┌──────────────────────┐    │ │
│  │  │ show()           │         │ update()             │    │ │
│  │  │ - Load user      │         │ - Validate request   │    │ │
│  │  │ - Eager load     │         │ - Update user        │    │ │
│  │  │ - Return view    │         │ - Hash password      │    │ │
│  │  └──────────────────┘         └──────────────────────┘    │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                   │
│                              ▼                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │         TenantUpdateProfileRequest (FormRequest)           │ │
│  │  ┌──────────────────────────────────────────────────────┐ │ │
│  │  │ Validation Rules:                                     │ │ │
│  │  │ - name: required, string, max:255                    │ │ │
│  │  │ - email: required, email, unique                     │ │ │
│  │  │ - current_password: required_with:password           │ │ │
│  │  │ - password: nullable, min:8, confirmed               │ │ │
│  │  └──────────────────────────────────────────────────────┘ │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                   │
│                              ▼                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                      User Model                             │ │
│  │  ┌──────────────────────────────────────────────────────┐ │ │
│  │  │ Relationships:                                        │ │ │
│  │  │ - property (BelongsTo)                               │ │ │
│  │  │ - parentUser (BelongsTo)                             │ │ │
│  │  │                                                       │ │ │
│  │  │ Methods:                                              │ │ │
│  │  │ - fill() - Mass assignment                           │ │ │
│  │  │ - save() - Persist changes                           │ │ │
│  │  └──────────────────────────────────────────────────────┘ │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                   │
│                              ▼                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                      Database Layer                         │ │
│  │  ┌──────────────────────────────────────────────────────┐ │ │
│  │  │ users table:                                          │ │ │
│  │  │ - id, name, email, password                          │ │ │
│  │  │ - role, tenant_id, property_id                       │ │ │
│  │  │ - parent_user_id, is_active                          │ │ │
│  │  │ - created_at, updated_at                             │ │ │
│  │  └──────────────────────────────────────────────────────┘ │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

## Component Interaction Flow

### Profile Display Flow

```
User Request
    │
    ▼
┌─────────────────┐
│ GET /tenant/    │
│ profile         │
└─────────────────┘
    │
    ▼
┌─────────────────┐
│ Middleware      │
│ Validation      │
│ - auth          │
│ - role:tenant   │
└─────────────────┘
    │
    ▼
┌─────────────────┐
│ ProfileController│
│ ::show()        │
└─────────────────┘
    │
    ▼
┌─────────────────┐
│ Load User with  │
│ Relationships:  │
│ - property      │
│ - building      │
│ - parentUser    │
└─────────────────┘
    │
    ▼
┌─────────────────┐
│ Return Blade    │
│ View with Data  │
└─────────────────┘
    │
    ▼
┌─────────────────┐
│ Render HTML     │
│ Response        │
└─────────────────┘
```

### Profile Update Flow

```
User Submission
    │
    ▼
┌─────────────────┐
│ PUT /tenant/    │
│ profile         │
│ + Form Data     │
│ + CSRF Token    │
└─────────────────┘
    │
    ▼
┌─────────────────┐
│ CSRF Validation │
└─────────────────┘
    │
    ▼
┌─────────────────┐
│ Middleware      │
│ Validation      │
└─────────────────┘
    │
    ▼
┌─────────────────┐
│ FormRequest     │
│ Validation      │
│ - name rules    │
│ - email rules   │
│ - password rules│
└─────────────────┘
    │
    ├─ Validation Failed ──┐
    │                      │
    │                      ▼
    │              ┌─────────────────┐
    │              │ Redirect Back   │
    │              │ with Errors     │
    │              └─────────────────┘
    │
    ▼
┌─────────────────┐
│ ProfileController│
│ ::update()      │
└─────────────────┘
    │
    ▼
┌─────────────────┐
│ Update User     │
│ - fill() name   │
│ - fill() email  │
│ - Hash password │
└─────────────────┘
    │
    ▼
┌─────────────────┐
│ save() to DB    │
└─────────────────┘
    │
    ▼
┌─────────────────┐
│ Redirect Back   │
│ with Success    │
└─────────────────┘
```

## Security Architecture

### Defense Layers

```
┌─────────────────────────────────────────────────────────────┐
│                     Security Layers                          │
│                                                              │
│  Layer 1: Network Security                                  │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ - HTTPS/TLS encryption                                 │ │
│  │ - Firewall rules                                       │ │
│  └────────────────────────────────────────────────────────┘ │
│                          │                                   │
│                          ▼                                   │
│  Layer 2: Application Middleware                            │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ - Authentication (auth)                                │ │
│  │ - Authorization (role:tenant)                          │ │
│  │ - Subscription check                                   │ │
│  │ - Hierarchical access                                  │ │
│  └────────────────────────────────────────────────────────┘ │
│                          │                                   │
│                          ▼                                   │
│  Layer 3: CSRF Protection                                   │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ - Token generation                                     │ │
│  │ - Token validation                                     │ │
│  │ - Session binding                                      │ │
│  └────────────────────────────────────────────────────────┘ │
│                          │                                   │
│                          ▼                                   │
│  Layer 4: Input Validation                                  │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ - FormRequest validation                               │ │
│  │ - Type checking                                        │ │
│  │ - Length limits                                        │ │
│  │ - Format validation                                    │ │
│  └────────────────────────────────────────────────────────┘ │
│                          │                                   │
│                          ▼                                   │
│  Layer 5: Password Security                                 │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ - Current password verification                        │ │
│  │ - Password hashing (bcrypt)                            │ │
│  │ - Password confirmation                                │ │
│  │ - Minimum length requirement                           │ │
│  └────────────────────────────────────────────────────────┘ │
│                          │                                   │
│                          ▼                                   │
│  Layer 6: Data Protection                                   │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ - SQL injection prevention (Eloquent ORM)              │ │
│  │ - XSS prevention (Blade escaping)                      │ │
│  │ - Email uniqueness check                               │ │
│  └────────────────────────────────────────────────────────┘ │
│                          │                                   │
│                          ▼                                   │
│  Layer 7: Session Management                                │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ - Session regeneration                                 │ │
│  │ - Session timeout                                      │ │
│  │ - Secure cookie flags                                  │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## Data Model

### User Entity

```
┌─────────────────────────────────────────────────────────────┐
│                         users                                │
├─────────────────────────────────────────────────────────────┤
│ id                    : bigint (PK)                          │
│ name                  : string(255)                          │
│ email                 : string(255) UNIQUE                   │
│ password              : string(255) HASHED                   │
│ role                  : enum(superadmin,admin,manager,tenant)│
│ tenant_id             : bigint (FK → users.id)               │
│ property_id           : bigint (FK → properties.id) NULLABLE │
│ parent_user_id        : bigint (FK → users.id) NULLABLE      │
│ organization_name     : string(255) NULLABLE                 │
│ is_active             : boolean DEFAULT true                 │
│ email_verified_at     : timestamp NULLABLE                   │
│ remember_token        : string(100) NULLABLE                 │
│ created_at            : timestamp                            │
│ updated_at            : timestamp                            │
└─────────────────────────────────────────────────────────────┘
         │                    │                    │
         │                    │                    │
         ▼                    ▼                    ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│ properties   │    │ users        │    │ users        │
│ (property)   │    │ (tenant)     │    │ (parentUser) │
└──────────────┘    └──────────────┘    └──────────────┘
```

### Relationships

```
User (Tenant)
├── property (BelongsTo)
│   └── Property
│       ├── id
│       ├── address
│       ├── type
│       ├── area_sqm
│       └── building (BelongsTo)
│           └── Building
│               ├── id
│               ├── name
│               └── address
└── parentUser (BelongsTo)
    └── User (Admin/Manager)
        ├── id
        ├── name
        ├── email
        └── organization_name
```

## Validation Architecture

### Validation Rules Hierarchy

```
TenantUpdateProfileRequest
│
├── Name Validation
│   ├── required
│   ├── string
│   └── max:255
│
├── Email Validation
│   ├── required
│   ├── email (format)
│   └── unique:users,email,{user_id}
│
├── Current Password Validation
│   ├── nullable
│   ├── required_with:password
│   ├── string
│   └── current_password (Laravel 12 rule)
│
└── New Password Validation
    ├── nullable
    ├── string
    ├── min:8
    └── confirmed (matches password_confirmation)
```

### Validation Flow

```
Form Submission
    │
    ▼
┌─────────────────┐
│ Laravel Request │
│ Pipeline        │
└─────────────────┘
    │
    ▼
┌─────────────────┐
│ FormRequest     │
│ authorize()     │
│ returns true    │
└─────────────────┘
    │
    ▼
┌─────────────────┐
│ FormRequest     │
│ rules()         │
│ defines rules   │
└─────────────────┘
    │
    ▼
┌─────────────────┐
│ Laravel         │
│ Validator       │
│ validates input │
└─────────────────┘
    │
    ├─ Valid ──────────┐
    │                  │
    │                  ▼
    │          ┌─────────────────┐
    │          │ Controller      │
    │          │ receives        │
    │          │ validated data  │
    │          └─────────────────┘
    │
    ▼
┌─────────────────┐
│ Invalid         │
│ Redirect back   │
│ with errors     │
└─────────────────┘
```

## Performance Characteristics

### Database Query Analysis

**Profile Show (GET /tenant/profile)**:
```sql
-- Single query with eager loading
SELECT * FROM users 
WHERE id = ? 
LIMIT 1;

SELECT * FROM properties 
WHERE id IN (?);

SELECT * FROM buildings 
WHERE id IN (?);

SELECT * FROM users 
WHERE id IN (?);

-- Total: 4 queries (optimized with eager loading)
-- Alternative without eager loading: 1 + N queries (N+1 problem)
```

**Profile Update (PUT /tenant/profile)**:
```sql
-- Single update query
UPDATE users 
SET name = ?, 
    email = ?, 
    password = ?, 
    updated_at = ? 
WHERE id = ?;

-- Total: 1 query
```

### Performance Metrics

| Operation | Queries | Response Time | Memory |
|-----------|---------|---------------|--------|
| Profile Show | 4 | < 100ms | ~2MB |
| Profile Update | 1 | < 150ms | ~1MB |
| Validation | 0-1* | < 50ms | ~0.5MB |

*Email uniqueness check requires 1 query

### Optimization Strategies

1. **Eager Loading**: Prevents N+1 queries
2. **Query Caching**: Session data cached
3. **Minimal Data Transfer**: Only necessary fields loaded
4. **Index Usage**: Indexed columns (id, email, tenant_id)

## Localization Architecture

### Translation Structure

```
lang/
├── en/
│   ├── tenant.php
│   │   └── profile.*
│   │       ├── update_profile
│   │       ├── labels.*
│   │       └── messages.*
│   └── users.php
│       └── validation.*
│           ├── name.*
│           ├── email.*
│           └── password.*
├── lt/
│   ├── tenant.php (Lithuanian)
│   └── users.php (Lithuanian)
└── ru/
    ├── tenant.php (Russian)
    └── users.php (Russian)
```

### Translation Loading Flow

```
Request
    │
    ▼
┌─────────────────┐
│ Detect Locale   │
│ from session    │
└─────────────────┘
    │
    ▼
┌─────────────────┐
│ Load Translation│
│ Files for Locale│
└─────────────────┘
    │
    ▼
┌─────────────────┐
│ __() Helper     │
│ Resolves Keys   │
└─────────────────┘
    │
    ▼
┌─────────────────┐
│ Fallback to EN  │
│ if Missing      │
└─────────────────┘
```

## Testing Architecture

### Test Pyramid

```
                    ┌─────────────┐
                    │   Manual    │ (1 test)
                    │   Testing   │
                    └─────────────┘
                          │
                ┌─────────────────────┐
                │   Feature Tests     │ (14 tests)
                │   - Happy paths     │
                │   - Validation      │
                │   - Authorization   │
                └─────────────────────┘
                          │
            ┌─────────────────────────────┐
            │      Unit Tests             │ (Implicit)
            │      - FormRequest rules    │
            │      - Model methods        │
            │      - Helper functions     │
            └─────────────────────────────┘
```

### Test Coverage Map

```
ProfileController
├── show() ✅ Covered
│   ├── Authenticated access ✅
│   ├── Unauthenticated access ✅
│   └── Non-tenant access ✅
└── update() ✅ Covered
    ├── Valid update ✅
    ├── Name validation ✅
    ├── Email validation ✅
    ├── Password validation ✅
    ├── Current password check ✅
    └── Success message ✅

TenantUpdateProfileRequest
├── rules() ✅ Covered
│   ├── Name rules ✅
│   ├── Email rules ✅
│   ├── Current password rules ✅
│   └── Password rules ✅
└── messages() ✅ Covered
    └── Localized messages ✅
```

## Deployment Architecture

### Deployment Flow

```
┌─────────────────┐
│ Code Repository │
│ (Git)           │
└─────────────────┘
        │
        ▼
┌─────────────────┐
│ CI/CD Pipeline  │
│ - Run tests     │
│ - Check style   │
│ - Build assets  │
└─────────────────┘
        │
        ▼
┌─────────────────┐
│ Staging Server  │
│ - Deploy code   │
│ - Run migrations│
│ - Test manually │
└─────────────────┘
        │
        ▼
┌─────────────────┐
│ Production      │
│ - Deploy code   │
│ - Run migrations│
│ - Clear caches  │
│ - Monitor       │
└─────────────────┘
```

### Deployment Checklist

- [ ] Run tests: `php artisan test --filter=ProfileUpdateTest`
- [ ] Check code style: `./vendor/bin/pint --test`
- [ ] Run static analysis: `./vendor/bin/phpstan analyse`
- [ ] Deploy code to staging
- [ ] Run migrations: `php artisan migrate`
- [ ] Clear caches: `php artisan cache:clear`
- [ ] Clear views: `php artisan view:clear`
- [ ] Test manually in staging
- [ ] Deploy to production
- [ ] Monitor error logs
- [ ] Verify functionality

## Monitoring & Observability

### Key Metrics to Monitor

1. **Performance Metrics**:
   - Profile page load time
   - Profile update response time
   - Database query time
   - Memory usage

2. **Error Metrics**:
   - Validation error rate
   - Authentication failures
   - Database errors
   - 500 errors

3. **Usage Metrics**:
   - Profile views per day
   - Profile updates per day
   - Password changes per day
   - Failed password attempts

4. **Security Metrics**:
   - Failed authentication attempts
   - CSRF token failures
   - Suspicious activity patterns

### Logging Strategy

```
Application Logs
├── Info Level
│   ├── Profile viewed
│   └── Profile updated
├── Warning Level
│   ├── Validation failures
│   └── Failed password attempts
└── Error Level
    ├── Database errors
    ├── Authentication errors
    └── Unexpected exceptions
```

## Future Architecture Considerations

### Planned Enhancements

1. **Email Verification**:
   - Add email verification flow
   - Temporary email storage
   - Verification token generation

2. **Audit Logging**:
   - Log all profile changes
   - Track who made changes
   - Store old and new values

3. **Rate Limiting**:
   - Limit profile updates per hour
   - Prevent abuse
   - Throttle password attempts

4. **Two-Factor Authentication**:
   - Add 2FA setup in profile
   - QR code generation
   - Backup codes

5. **Profile Picture**:
   - File upload handling
   - Image processing
   - Storage management

### Scalability Considerations

1. **Database Scaling**:
   - Read replicas for profile views
   - Write master for updates
   - Connection pooling

2. **Caching Strategy**:
   - Cache user data
   - Cache translations
   - Cache validation rules

3. **Load Balancing**:
   - Multiple application servers
   - Session affinity
   - Shared session storage

## Related Documentation

- [Tenant Profile Update Feature](docs/features/TENANT_PROFILE_UPDATE.md)
- [Tenant Profile API](docs/api/tenant-profile-api.md)
- [Hierarchical User Management Spec](.kiro/specs/3-hierarchical-user-management/)
- [Security Best Practices](docs/security/BEST_PRACTICES.md)
- [Performance Optimization](docs/performance/OPTIMIZATION.md)
