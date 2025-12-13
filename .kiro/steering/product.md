---
inclusion: always
---

# CFlow Product Overview

CFlow is a comprehensive web-based accounting and invoicing platform designed specifically for Lithuanian individual activity (sole proprietorship) businesses.

## Core Purpose

Automates tax calculations, invoice generation, expense tracking, VAT declarations, and provides complete accounting management for freelancers and small business owners in Lithuania.

## Key Features

### Invoice Management
- Multi-currency invoices with automatic EUR conversion
- PDF generation with Lithuanian tax compliance formatting
- Credit invoices and future invoice scheduling
- Invoice templates and recurring invoice automation
- Public invoice sharing with secure access tokens

### Tax Compliance
- Lithuanian tax calculations (GPM, PSD, VSD)
- VAT threshold monitoring with automatic notifications
- VAT declaration generation and submission
- Tax payment tracking and reporting
- EVRK economic activity classification

### Client & Supplier Management
- Unified client/supplier directory with transaction history
- Company and individual contact management
- Client categorization and relationship tracking
- Integration with invoice and expense workflows

### Multi-Currency Support
- EUR, USD, GBP, NOK, DKK currency support
- Daily exchange rate fetching from European Central Bank (ECB)
- Automatic currency conversion for tax reporting
- Historical exchange rate tracking

### Background Processing
- Three-tier priority queue system (high/normal/low)
- Async invoice generation and email delivery
- Scheduled tax calculations and notifications
- Background data synchronization and cleanup

### Multilingual System
- Dynamic database-driven translation system
- Lithuanian and English language support
- Translatable content for invoices and reports
- Locale-aware number and date formatting

## Target Users

- **Primary**: Lithuanian freelancers and sole proprietors
- **Secondary**: Small business owners managing individual activity businesses
- **Tertiary**: Accountants and bookkeepers serving Lithuanian clients

## Technical Architecture

### Current Status
- Complete rewrite from Ruby on Rails to Laravel 12
- Phase 1 (core infrastructure and invoice system) complete with 108+ passing tests
- Phase 2 (expense management and tax calculations) in active development
- Filament v4.3+ admin panels for user and admin interfaces

### Key Technical Decisions
- **Database Caching**: Use database cache driver for session storage and application caching
- **Multi-Tenancy**: Team-based tenancy with automatic scoping in Filament
- **Queue System**: Database-backed queues with three priority levels
- **File Storage**: Local storage for development, configurable for production
- **Currency Data**: ECB API integration for real-time exchange rates

## Business Rules

### Lithuanian Tax Compliance
- All monetary amounts must support EUR conversion for tax reporting
- VAT calculations must follow Lithuanian tax authority requirements
- Invoice numbering must be sequential within each activity book
- Tax periods align with Lithuanian fiscal calendar

### Data Integrity
- All financial transactions must be auditable
- Currency conversions must use official ECB rates
- Invoice modifications create audit trails
- User actions are logged for compliance

### User Experience
- Interface defaults to Lithuanian locale for Lithuanian users
- All user-facing text must be translatable
- Financial data displays in user's preferred currency
- Tax notifications are proactive and contextual

## Development Guidelines

### Code Organization
- Business logic in Action classes (`app/Actions/`)
- Domain models with rich behavior (avoid anemic models)
- Service classes for complex operations (`app/Services/`)
- Repository pattern for data access abstraction

### Testing Requirements
- Feature tests for all user workflows
- Unit tests for business logic and calculations
- Integration tests for external API dependencies (ECB rates)
- Filament resource tests for admin panel functionality

### Performance Considerations
- Cache expensive calculations (tax rates, currency conversions)
- Use database transactions for multi-step operations
- Optimize queries with eager loading for reports
- Background processing for heavy operations (PDF generation, email sending)

### Security & Compliance
- All financial data must be encrypted at rest
- User permissions follow role-based access control
- API endpoints require authentication and rate limiting
- Audit logs for all financial transactions and user actions