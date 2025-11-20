# Product Overview

## Vilnius Utilities Billing System

A monolithic web application for managing utility billing in the Vilnius (Lithuania) rental property market. The system automates calculation and tracking of utility payments for property management companies and their tenants.

## Core Purpose

Automates complex utility billing calculations specific to the Lithuanian market, including:
- Multi-tariff electricity plans (day/night rates)
- Regulated water supply tariffs (Vilniaus Vandenys)
- Seasonal heating calculations with "gyvatukas" (hot water circulation fees)
- Snapshot-based invoicing that preserves historical pricing

## Key Users

- **Admins**: Configure tariffs, manage tenant accounts, system administration
- **Managers**: Enter meter readings, generate invoices, view tenant data
- **Tenants**: View their invoices and consumption history

## Technical Approach

Majestic Monolith architecture using Laravel 11 with SQLite, server-side rendering (Blade templates) enhanced with Alpine.js for reactive UI. Single-database multi-tenancy with automatic data isolation via global scopes.

## Domain Specifics

The system handles three Lithuanian utility providers:
- **Ignitis**: Electricity with time-of-use pricing
- **Vilniaus Vandenys**: Water supply and sewage with fixed meter fees
- **Vilniaus Energija**: Heating with seasonal circulation fee calculations

The "gyvatukas" calculation is a unique requirement: circulation fees are calculated differently in summer (May-September) versus winter (October-April), using stored summer averages as norms during heating season.
