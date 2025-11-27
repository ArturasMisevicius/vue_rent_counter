# Middleware Implementation Spec: Subscription & Hierarchical Access

## Executive Summary

Implement two critical middleware components for the hierarchical user management system: `CheckSubscriptionStatus` for subscription enforcement and `EnsureHierarchicalAccess` for resource-level access validation. These middleware complete the authorization layer by enforcing subscription limits and validating hierarchical access patterns at the HTTP layer.

**Success Metrics:**
- 100% of admin routes protected by subscription checks
- <5ms middleware overhead per request
- Zero false positives in access denial
- 100% test coverage with property-based tests
- Audit logging for all access denials

**Constraints:**
- Must maintain backward compatibility with existing routes
- Cannot break existing authorization flows
- Must respect read-only mode for expired subscriptions
- Performance budget: <5ms per middleware execution

---

## User Stories

### Story 1: Subscription Status Enforcement

**As an** Admin with an expired subscription  
**I want** the system to restrict my access to read-only mode  
**So that** I can view my data but cannot make changes until renewal

#### Acceptance Criteria

**Functional:**
- GIVEN I am an Admin with an active subscription
- WHEN I access any admin route
- THEN the middleware allows full access without restrictions

- GIVEN I am an Admin with an expired subscription
- WHEN I access a mutating route (POST/PUT/DELETE)
- THEN the middleware redirects to subscription renewal page
- AND displays a clear expiry message

- GIVEN I am an Admin with an expired subscription
- WHEN I access a read-only route (GET)
- THEN the middleware allows access
- AND displ