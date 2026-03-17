# Delta for Tenant Profile Management

## ADDED Requirements

### Requirement: Tenant Profile Editing

The system SHALL let tenants update their profile details and preferred locale
from a tenant-facing profile page.

#### Scenario: Tenant updates profile details and language

- GIVEN an authenticated tenant on the profile page
- WHEN the tenant submits valid profile details including a new preferred locale
- THEN the system persists the updated profile information
- AND the system redirects back to the tenant profile page

#### Scenario: Locale change affects the redirected page

- GIVEN an authenticated tenant whose locale is changed successfully
- WHEN the tenant is redirected back to the profile page
- THEN the redirected page is rendered in the newly selected locale

### Requirement: Tenant Password Updates

The system SHALL require the current password and valid confirmation before a
tenant password change is accepted.

#### Scenario: Tenant submits a password change without the current password

- GIVEN an authenticated tenant on the profile page
- WHEN the tenant attempts to change the password without the correct current
  password
- THEN the system rejects the request with a validation error

#### Scenario: Tenant submits mismatched password confirmation

- GIVEN an authenticated tenant on the profile page
- WHEN the tenant submits a new password with mismatched confirmation
- THEN the system rejects the request with a validation error
