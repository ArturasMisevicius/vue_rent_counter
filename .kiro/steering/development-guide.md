---
inclusion: always
---

# Laravel TALL Stack Development Guide

## Core Principles
- Prioritize performance optimization and minimal JavaScript for optimal user experience.
- Use functional and declarative programming patterns in PHP; avoid unnecessary classes except for domain models and services.
- Prefer iteration and modularization over code duplication.
- Structure files: Blade components (markup), Alpine.js (interactivity), Tailwind CSS (styles).
- Use kebab-case for Blade component files (e.g., `components/auth-form.blade.php`).
- Use PascalCase for PHP class names and camelCase for variables, functions, and properties.

## PHP Development
- Use PHP 8.4+ features when appropriate (typed properties, match expressions, readonly properties).
- Always declare strict types: `declare(strict_types=1);`
- Prefer readonly properties and final classes by default.
- Use backed enums for constants and state management.
- Enable strict type checking throughout the codebase.

## UI and Styling
- Use Tailwind CSS for utility-first styling approach.
- Leverage DaisyUI components for pre-built, customizable UI elements.
- Use Blade components for reusable UI elements.
- Use Alpine.js for lightweight client-side interactivity.
- Organize Tailwind classes logically (layout → spacing → colors → typography).

## Tailwind Color Conventions
- Use semantic color naming for consistency.
- Define CSS variables for theme colors:
  ```css
  --primary: theme('colors.blue.600');
  --secondary: theme('colors.gray.600');
  --accent: theme('colors.purple.600');
  ```
- Key color variables to use:
  - `--background`, `--foreground`: Default body colors
  - `--muted`, `--muted-foreground`: Muted backgrounds
  - `--card`, `--card-foreground`: Card backgrounds
  - `--border`: Default border color
  - `--input`: Input border color
  - `--primary`, `--primary-foreground`: Primary button colors
  - `--secondary`, `--secondary-foreground`: Secondary button colors
  - `--accent`, `--accent-foreground`: Accent colors
  - `--destructive`, `--destructive-foreground`: Destructive action colors
  - `--ring`: Focus ring color
  - `--radius`: Border radius for components

## Performance Optimization
- Use Laravel's built-in caching mechanisms (Redis, file cache).
- Implement lazy loading for images and heavy components.
- Use Vite for asset bundling and optimization.
- Leverage Laravel's query optimization (eager loading, select specific columns).
- Profile and monitor performance using Laravel Telescope and browser developer tools.

## SEO and Meta Tags
- Use Blade layouts to manage meta tags consistently.
- Implement canonical URLs for proper SEO.
- Create reusable SEO Blade components for consistent meta tag management.
- Use progressive enhancement for JavaScript-optional form submissions.
- Implement proper Open Graph and Twitter Card meta tags.
- writing blade files write in existing files comments

## Accessibility
- Ensure proper semantic HTML structure in Blade components.
- Implement ARIA attributes where necessary.
- Ensure keyboard navigation support for interactive elements.
- Use Alpine.js `$focus` magic for managing focus programmatically.
- Test with screen readers and accessibility tools.

## Key Conventions
1. Follow Laravel's conventions and avoid over-engineering solutions.
2. Use Laravel Livewire for dynamic components when Alpine.js is insufficient.
3. Prioritize Web Vitals (LCP, FID, CLS) for performance optimization.
4. Use environment variables for configuration management.
5. Follow TALL stack best practices for component composition and state management.
6. Ensure cross-browser compatibility by testing on multiple platforms.
7. Use Laravel's validation and Form Requests for all user input.
8. Implement proper CSRF protection for all forms.
9. Use Laravel's authentication and authorization features (Sanctum, Policies).
10. Write tests for all critical functionality (PHPUnit, Pest, Dusk).


DO NOT CREATE ANY MD FILES !!!!