# Smoke Test Checklist - Hybrid Architecture Validation

## Overview

This document provides a manual smoke test checklist to validate the hybrid architecture:
- **Superadmin Panel**: Filament v4 at `/superadmin`
- **Admin Panel**: Filament v4 at `/admin`
- **Tenant Panel**: Filament v4 at `/tenant`
- **Manager Panel**: Custom Laravel MVC at `/manager/*`

## Test Credentials

| Role | Email | Password | Tenant ID | Panel |
|------|-------|----------|-----------|-------|
| Superadmin | `superadmin@example.com` | `password` | null | `/superadmin` |
| Admin | `admin@example.com` | `password` | 1 | `/admin` |
| Manager | `manager@example.com` | `password` | 1 | `/manager` |
| Tenant | `tenant@example.com` | `password` | 1 | `/tenant` |

---

## 1. Superadmin Panel (`/superadmin`) - Filament v4

### 1.1 Authentication
- [ ] Navigate to `/superadmin`
- [ ] Login form displays correctly
- [ ] Login with `superadmin@example.com` / `password`
- [ ] Redirects to superadmin dashboard after login
- [ ] Logout works correctly

### 1.2 Dashboard
- [ ] Dashboard loads without errors
- [ ] System Overview widget displays
- [ ] Recent Users widget displays
- [ ] Account widget displays

### 1.3 Navigation
- [ ] Organizations menu item visible
- [ ] Subscriptions menu item visible
- [ ] Platform Users menu item visible
- [ ] Navigation groups collapse/expand

### 1.4 Resources (CRUD)
- [ ] Can view Organizations list
- [ ] Can create new Organization
- [ ] Can edit existing Organization
- [ ] Can view Subscriptions list
- [ ] Can view Platform Users list

### 1.5 Authorization
- [ ] Non-superadmin users cannot access `/superadmin`
- [ ] Admin users redirected/blocked from superadmin panel
- [ ] Manager users redirected/blocked from superadmin panel
- [ ] Tenant users redirected/blocked from superadmin panel

---

## 2. Admin Panel (`/admin`) - Filament v4

### 2.1 Authentication
- [ ] Navigate to `/admin`
- [ ] Login form displays correctly
- [ ] Login with `admin@example.com` / `password`
- [ ] Redirects to admin dashboard after login
- [ ] Logout works correctly

### 2.2 Dashboard
- [ ] Dashboard loads without errors
- [ ] Widgets display correctly
- [ ] Account widget displays

### 2.3 Navigation
- [ ] Property Management group visible
- [ ] Billing group visible
- [ ] Administration group visible
- [ ] Navigation groups collapse/expand

### 2.4 Resources (CRUD)
- [ ] Can view Properties list
- [ ] Can create new Property
- [ ] Can view Buildings list
- [ ] Can view Meters list
- [ ] Can view Invoices list
- [ ] Can view Tariffs list

### 2.5 Tenant Scoping
- [ ] Only sees data for tenant_id=1
- [ ] Cannot access data from other tenants
- [ ] New records automatically scoped to tenant

### 2.6 Authorization
- [ ] Tenant users cannot access `/admin`
- [ ] Manager users CAN access `/admin` (per middleware)

---

## 3. Tenant Panel (`/tenant`) - Filament v4

### 3.1 Authentication
- [ ] Navigate to `/tenant`
- [ ] Login form displays correctly
- [ ] Login with `tenant@example.com` / `password`
- [ ] Redirects to tenant dashboard after login
- [ ] Logout works correctly

### 3.2 Dashboard
- [ ] Dashboard loads without errors
- [ ] Account widget displays
- [ ] Property information visible

### 3.3 Navigation
- [ ] My Property group visible
- [ ] Billing group visible
- [ ] Account group visible

### 3.4 Resources (Read-Only)
- [ ] Can view assigned Property
- [ ] Can view Meter Readings
- [ ] Can view Invoices
- [ ] Can submit new Meter Reading

### 3.5 Property Scoping
- [ ] Only sees own property data
- [ ] Cannot access other tenants' data
- [ ] Cannot modify property details

### 3.6 Authorization
- [ ] Admin users cannot access `/tenant`
- [ ] Manager users cannot access `/tenant`
- [ ] Superadmin users cannot access `/tenant`

---

## 4. Manager Panel (`/manager`) - Custom Laravel MVC

### 4.1 Authentication
- [ ] Navigate to `/manager/dashboard`
- [ ] Redirects to login if not authenticated
- [ ] Login with `manager@example.com` / `password`
- [ ] Redirects to manager dashboard after login
- [ ] Logout works correctly

### 4.2 Dashboard
- [ ] Dashboard loads without errors
- [ ] Statistics/overview displays
- [ ] Recent activity visible

### 4.3 Navigation
- [ ] Properties link works
- [ ] Buildings link works
- [ ] Meters link works
- [ ] Meter Readings link works
- [ ] Invoices link works
- [ ] Reports link works

### 4.4 Properties Management
- [ ] Can view Properties list (`/manager/properties`)
- [ ] Can view Property details (`/manager/properties/{id}`)
- [ ] Can create new Property
- [ ] Can edit existing Property

### 4.5 Buildings Management
- [ ] Can view Buildings list (`/manager/buildings`)
- [ ] Can view Building details
- [ ] Can create new Building
- [ ] Can edit existing Building

### 4.6 Meters Management
- [ ] Can view Meters list (`/manager/meters`)
- [ ] Can view Meter details
- [ ] Can create new Meter
- [ ] Can edit existing Meter

### 4.7 Meter Readings
- [ ] Can view Meter Readings list (`/manager/meter-readings`)
- [ ] Can create new Meter Reading
- [ ] Can correct existing Meter Reading
- [ ] Validation works (monotonic readings)

### 4.8 Invoices
- [ ] Can view Invoices list (`/manager/invoices`)
- [ ] Can view Draft invoices (`/manager/invoices/drafts`)
- [ ] Can view Finalized invoices (`/manager/invoices/finalized`)
- [ ] Can create new Invoice
- [ ] Can finalize Invoice
- [ ] Can mark Invoice as paid

### 4.9 Reports
- [ ] Reports index loads (`/manager/reports`)
- [ ] Consumption report works (`/manager/reports/consumption`)
- [ ] Revenue report works (`/manager/reports/revenue`)
- [ ] Compliance report works (`/manager/reports/meter-reading-compliance`)
- [ ] Export functionality works

### 4.10 Tenant Scoping
- [ ] Only sees data for tenant_id=1
- [ ] Cannot access data from other tenants

### 4.11 Authorization
- [ ] Tenant users cannot access `/manager/*`
- [ ] Admin users CAN access `/manager/*` (per middleware)

---

## 5. Cross-Panel Navigation

### 5.1 Unified Dashboard Route
- [ ] `/dashboard` redirects superadmin to `/superadmin`
- [ ] `/dashboard` redirects admin to `/admin`
- [ ] `/dashboard` redirects manager to `/manager/dashboard`
- [ ] `/dashboard` redirects tenant to `/tenant/dashboard`

### 5.2 Convenience Redirects
- [ ] `/manager` redirects to `/manager/dashboard`
- [ ] `/tenant` redirects to `/tenant/dashboard`

### 5.3 Language Switching
- [ ] Language switcher visible in all panels
- [ ] Switching language works
- [ ] Language persists across pages

---

## 6. Error Handling

### 6.1 404 Pages
- [ ] Non-existent routes show 404 page
- [ ] 404 page styled correctly

### 6.2 403 Pages
- [ ] Unauthorized access shows 403 page
- [ ] 403 page styled correctly

### 6.3 500 Pages
- [ ] Server errors show 500 page (if applicable)

---

## Test Results Summary

| Panel | Status | Issues Found |
|-------|--------|--------------|
| Superadmin | ⬜ Pending | |
| Admin | ⬜ Pending | |
| Tenant | ⬜ Pending | |
| Manager | ⬜ Pending | |

### Legend
- ✅ Pass
- ❌ Fail
- ⬜ Pending
- ⚠️ Partial

---

## Notes

_Record any issues, observations, or bugs found during testing:_

1. 
2. 
3. 

---

## Tester Information

- **Date**: 
- **Tester**: 
- **Environment**: Local Development
- **PHP Version**: 
- **Laravel Version**: 12.x
- **Filament Version**: 4.x
