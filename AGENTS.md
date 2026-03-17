<!-- OPENSPEC:START -->
# OpenSpec Instructions

These instructions are for AI assistants working in this project.

Always open `@/openspec/AGENTS.md` when the request:
- Mentions planning or proposals (words like proposal, spec, change, plan)
- Introduces new capabilities, breaking changes, architecture shifts, or big performance/security work
- Sounds ambiguous and you need the authoritative spec before coding

Use `@/openspec/AGENTS.md` to learn:
- How to create and apply change proposals
- Spec format and conventions
- Project structure and guidelines

Keep this managed block so 'openspec update' can refresh the instructions.

<!-- OPENSPEC:END -->

## Tenanto Current Repo Context

Use `docs/PROJECT-CONTEXT.md` as the canonical workspace snapshot when a pasted session brief conflicts with the live repository.
Use `docs/SESSION-BOOTSTRAP.md` for the verified start-of-session MCP, skills, and baseline verification flow.

Verified snapshot for this workspace on 2026-03-17:

- Local CLI runtime is PHP `8.5.4`; `composer.json` currently requires PHP `^8.2`.
- Core stack is Laravel `12`, Filament `5.3`, Livewire `4`, Tailwind CSS `4`, Pest `4`, PHPUnit `12`, Alpine.js `3`, and Sanctum `4`.
- Tenanto is currently a Filament-first, Livewire-assisted multi-tenant utility billing and property management application.
- Role enum values are `SUPERADMIN`, `ADMIN`, `MANAGER`, and `TENANT`.
- Current application surface includes 1 remaining base controller, 17 Filament resources, 27 Livewire components, 84 test files, and 1 Filament panel provider at `app/Providers/Filament/AdminPanelProvider.php`.
- The shared application foundation for requests, actions, and support services lives under `app/Http/Requests`, `app/Filament/Actions`, and `app/Filament/Support`.
- The repository-local `.mcp.json` currently defines only the `herd` server. Do not assume repo-local `laravel-mcp` or `laravel-boost` entries unless you verify them again.
- The current application does not register `php artisan boost:mcp` or `php artisan mcp:start tenanto`; verify the Artisan command surface before documenting MCP startup steps as mandatory.
- The public web root currently exposes only `public/index.php`; do not introduce ad hoc public debug entrypoints.
- Keep `public/` free of debug probes, extra executable PHP files, and unused assets such as a stray `sw.js` without a real PWA implementation.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5.4
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v12
- laravel/mcp (MCP) - v0
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `mcp-development` — Use this skill for Laravel MCP development only. Trigger when creating or editing MCP tools, resources, prompts, or servers in Laravel projects. Covers: artisan make:mcp-* generators, mcp:inspector, routes/ai.php, Tool/Resource/Prompt classes, schema validation, shouldRegister(), OAuth setup, URI templates, read-only attributes, and MCP debugging. Do not use for non-Laravel MCP projects or generic AI features without MCP.
- `pest-testing` — Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, browser testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.
- `laravel-backup` — Configure and extend spatie/laravel-backup for database and file backups, cleanup strategies, health monitoring, and notifications. Activates when working with backup configuration, scheduling backups, creating custom cleanup strategies or health checks, customizing notifications, or when the user mentions backups, backup monitoring, backup cleanup, or spatie/laravel-backup.
- `acc-optimize-docker-php-fpm` — A reusable AI agent skill for acc-optimize-docker-php-fpm.
- `analyse-with-phpstan` — Analyse PHP code with PHPStan via the playground API. Tests across all PHP versions (7.2–8.5) and reports errors grouped by version. Supports configuring level, strict rules, and bleeding edge.
- `analyze-codebase-for-improvements` — Analyze a codebase or project to identify actionable improvements, architectural enhancements, and optimization opportunities. Use this skill when the user asks to review code quality, suggest improvements, identify bottlenecks, audit a system, or get recommendations for enhancing a project. Generates a prioritized list of improvements with problem statements, solutions, and impact analysis.
- `analyze-document` — Analyze and provide detailed feedback on documents including PDFs, text files, and other formats. Use this skill when the user asks for your thoughts on a document, wants a review, analysis, summary, critique, or evaluation of any written material. Supports document upload, file path references, or direct text content.
- `answer-factual-question` — Answer factual questions about events, people, places, and general knowledge topics. Use this skill when the user asks 'who won', 'what happened', 'when did', 'where is', or other questions seeking factual information about sports, history, geography, current events, or other verifiable facts.
- `api-resource-patterns` — Best practices for Laravel API Resources including resource transformation, collection handling, conditional attributes, and relationship loading.
- `appwrite-php` — Appwrite PHP SDK skill. Use when building server-side PHP applications with Appwrite, including Laravel and Symfony integrations. Covers user management, database/table CRUD, file storage, and functions via API keys.
- `brainstorming-laravel` — A reusable AI agent skill for brainstorming-laravel.
- `brand-guidelines` — Applies Anthropic's official brand colors and typography to any sort of artifact that may benefit from having Anthropic's look-and-feel. Use it when brand colors or style guidelines, visual formatting, or company design standards apply.
- `ccxt-php` — CCXT cryptocurrency exchange library for PHP developers. Covers both REST API (standard) and WebSocket API (real-time). Helps install CCXT, connect to exchanges, fetch market data, place orders, stream live tickers/orderbooks, handle authentication, and manage errors in PHP 8.1+. Use when working with crypto exchanges in PHP projects, trading bots, or web applications. Supports both sync and async (ReactPHP) usage.
- `checking-breaking-changes-in-php-framework` — A reusable AI agent skill for checking-breaking-changes-in-php-framework.
- `clean-architecture-php` — A reusable AI agent skill for clean-architecture-php.
- `clean-architecture-php-giuseppe-trisciuoglio` — A reusable AI agent skill for clean-architecture-php.
- `complexity-guardrails` — Keep cyclomatic complexity low; flatten control flow, extract helpers, and prefer table-driven/strategy patterns over large switches
- `csv-tools` — Parse, query, filter, sort, transform, and summarize CSV and JSON data files. Use this skill when the user asks to view a CSV, filter data, get statistics from a data file, convert CSV to JSON or vice versa, sort data, or analyze tabular data.
- `developing-with-prism` — Guide for developing with Prism PHP package - a Laravel package for integrating LLMs. Activate or use when working with Prism features including text generation, structured output, embeddings, image generation, audio processing, streaming, tools/function calling, or any LLM provider integration (OpenAI, Anthropic, Gemini, Mistral, Groq, XAI, DeepSeek, OpenRouter, Ollama, VoyageAI, ElevenLabs). Activate for any Prism-related development tasks.
- `eloquent-best-practices` — Best practices for Laravel Eloquent ORM including query optimization, relationship management, and avoiding common pitfalls like N+1 queries.
- `eloquent-best-practices-iserter` — Best practices for Laravel Eloquent ORM including query optimization, relationship management, and avoiding common pitfalls like N+1 queries.
- `filament` — A reusable AI agent skill for filament.
- `file-compress` — Create and extract ZIP and tar.gz archives. Use this skill when the user asks to zip files, unzip an archive, compress a folder, extract a tar.gz, create an archive, or list archive contents.
- `financial-analysis` — Analyze financial statements from PDF documents. Use when asked to prepare a financial analysis, review financial statements, analyze balance sheets, income statements, or cash flow statements. Triggers include: any mention of 'financial analysis', 'financial statements', 'balance sheet', 'income statement', 'cash flow', 'financial review', 'annual report analysis', or requests to analyze PDF documents containing financial data.
- `fluxui-development` — Use this skill for Flux UI development in Livewire applications only. Trigger when working with <flux:*> components, building or customizing Livewire component UIs, creating forms, modals, tables, or other interactive elements. Covers: flux: components (buttons, inputs, modals, forms, tables, date-pickers, kanban, badges, tooltips, etc.), component composition, Tailwind CSS styling, Heroicons/Lucide icon integration, validation patterns, responsive design, and theming. Do not use for non-Livewire frameworks or non-component styling.
- `freekmurze-php-guidelines-from-spatie` — A reusable AI agent skill for freekmurze-php-guidelines-from-spatie.
- `frontend-design` — Create distinctive, production-grade frontend interfaces with high design quality. Use this skill when the user asks to build web components, pages, artifacts, posters, or applications (examples include websites, landing pages, dashboards, React components, HTML/CSS layouts, or when styling/beautifying any web UI). Generates creative, polished code and UI design that avoids generic AI aesthetics.
- `greet-user` — Provide a friendly greeting and introduction to the AI assistant's capabilities. Use this skill when the user opens a conversation, says hello, or asks what the assistant can do. Helps orient new users to available features and sets a welcoming tone for interaction.
- `image-tools` — Resize, compress, convert, crop, and inspect images. Use this skill when the user asks to resize an image, compress a photo, convert image format (PNG to JPG etc), crop an image, get image dimensions/info, or optimize images for web.
- `laravel-11-12-app-guidelines` — Guidelines and workflow for working on Laravel 11 or Laravel 12 applications across common stacks (API-only or full-stack), including optional Docker Compose/Sail, Inertia + React, Livewire, Vue, Blade, Tailwind v4, Fortify, Wayfinder, PHPUnit, Pint, and Laravel Boost MCP tools. Use when implementing features, fixing bugs, or making UI/backend changes while following project-specific instructions (AGENTS.md, docs/).
- `laravel-actions` — Action-oriented architecture for Laravel. Invokable classes that contain domain logic. Use when working with business logic, domain operations, or when user mentions actions, invokable classes, or needs to organize domain logic outside controllers.
- `laravel-api` — Build production-grade Laravel REST APIs using opinionated architecture patterns with Laravel best practices. Use when building, scaffoling, or reviewing Laravel APIs with specifications for stateless design, versioned endpoints, invokable controllers, Form Request DTOs, Action classes, JWT authentication, and PSR-12 code quality standards. Triggers on "build a Laravel API", "create Laravel endpoints", "add API authentication", "review Laravel API code", "refactor Laravel API", or "improve Laravel code quality".
- `laravel-architecture` — High-level architecture decisions, patterns, and project structure. Use when user asks about architecture decisions, project structure, pattern selection, or mentions how to organize, which pattern to use, best practices, architecture.
- `laravel-best-practices` — Laravel 12 conventions and best practices. Use when creating controllers, models, migrations, validation, services, or structuring Laravel applications. Triggers on tasks involving Laravel architecture, Eloquent, database, API development, or PHP patterns.
- `laravel-controller-tests` — Write focused controller tests using HTTP assertions; keep heavy logic in Actions/Services and unit test them
- `laravel-controllers` — Thin HTTP layer controllers. Controllers contain zero domain logic, only HTTP concerns. Use when working with controllers, HTTP layer, web vs API patterns, or when user mentions controllers, routes, HTTP responses.
- `laravel-data-chunking-large-datasets` — Process large datasets efficiently using chunk(), chunkById(), lazy(), and cursor() to reduce memory consumption and improve performance
- `laravel-dtos` — Data Transfer Objects using Spatie Laravel Data. Use when handling data transfer, API requests/responses, or when user mentions DTOs, data objects, Spatie Data, formatters, transformers, or structured data handling.
- `laravel-dusk` — Laravel Dusk - Browser automation and testing API for Laravel applications. Use when writing browser tests, automating UI testing, testing JavaScript interactions, or implementing end-to-end tests in Laravel.
- `laravel-enums` — Backed enums with labels and business logic. Use when working with enums, status values, fixed sets of options, or when user mentions enums, backed enums, enum cases, status enums.
- `laravel-exceptions` — Custom exceptions with static factories and HTTP responses. Use when working with error handling, custom exceptions, or when user mentions exceptions, custom exception, error handling, HTTP exceptions.
- `laravel-expert` — Senior Laravel Engineer role for production-grade, maintainable, and idiomatic Laravel solutions. Focuses on clean architecture, security, performance, and modern standards (Laravel 10/11+).
- `laravel-inertia-react` — Laravel + Inertia.js + React integration patterns. Use when building Inertia page components, handling forms with useForm, managing shared data, or implementing persistent layouts. Triggers on tasks involving Inertia.js, page props, form handling, or Laravel React integration.
- `laravel-interfaces-and-di` — Use interfaces and dependency injection to decouple code; bind implementations in the container
- `laravel-iterating-on-code` — Refine AI-generated code through specific feedback—point out errors, identify gaps, show desired changes, reference style guides
- `laravel-jobs` — Background jobs and event listeners for async processing. Use when working with queued jobs, background processing, events, or when user mentions jobs, queues, listeners, events, async processing.
- `laravel-laravel-prompting-patterns` — A reusable AI agent skill for laravel-laravel-prompting-patterns.
- `laravel-models` — Eloquent model patterns and database layer. Use when working with models, database entities, Eloquent ORM, or when user mentions models, eloquent, relationships, casts, observers, database entities.
- `laravel-multi-tenancy` — Multi-tenant application architecture patterns. Use when working with multi-tenant systems, tenant isolation, or when user mentions multi-tenancy, tenants, tenant scoping, tenant isolation, multi-tenant.
- `laravel-packages` — Package development and extraction of reusable code. Use when working with package development, code reusability, or when user mentions packages, composer packages, extract package, reusable code, package development.
- `laravel-performance-caching` — Use framework caches and value/query caching to reduce work; add tags, locks, and explicit invalidation strategies for correctness
- `laravel-performance-eager-loading` — Prevent N+1 queries by eager loading; enable lazy-loading protection in non-production; choose selective fields
- `laravel-permission-development` — Build and work with Spatie Laravel Permission features, including roles, permissions, middleware, policies, teams, and Blade directives.
- `laravel-policies` — Authorization policies for resource access control. Use when working with authorization, permissions, access control, or when user mentions policies, authorization, permissions, can, ability checks.
- `laravel-policies-and-authorization` — Enforce access via Policies and Gates; use authorize() and authorizeResource() to standardize controller protections
- `laravel-providers` — Service providers, bootstrapping, and application configuration. Use when working with service providers, app configuration, bootstrapping, or when user mentions service providers, AppServiceProvider, bootstrap, booters, configuration, helpers.
- `laravel-quality` — Code quality tooling with PHPStan, Pint, and strict types. Use when working with code quality, static analysis, formatting, or when user mentions PHPStan, Pint, quality, static analysis, type safety, code style, linting.
- `laravel-query-builders` — Custom query builders for type-safe, composable database queries. Use when working with database queries, query scoping, or when user mentions query builders, custom query builder, query objects, query scopes, database queries.
- `laravel-routes-best-practices` — Keep routes clean and focused on mapping requests to controllers; avoid business logic, validation, or database operations in route files
- `laravel-routing` — Route configuration, route model binding, and authorization. Use when working with routes, route binding, URL patterns, or when user mentions routing, route model binding, conditional binding, route-level authorization.
- `laravel-security-audit` — Security auditor for Laravel applications. Analyzes code for vulnerabilities, misconfigurations, and insecure practices using OWASP standards and Laravel security best practices.
- `laravel-services` — Service layer for external API integration using manager pattern and Saloon. Use when working with external APIs, third-party services, or when user mentions services, external API, Saloon, API integration, manager pattern.
- `laravel-specialist` — Build and configure Laravel 10+ applications, including creating Eloquent models and relationships, implementing Sanctum authentication, configuring Horizon queues, designing RESTful APIs with API resources, and building reactive interfaces with Livewire. Use when creating Laravel models, setting up queue workers, implementing Sanctum auth flows, building Livewire components, optimising Eloquent queries, or writing Pest/PHPUnit tests for Laravel features.
- `laravel-state-machines` — State machines using Spatie Model States for complex state transitions. Use when working with complex state management, state transitions, or when user mentions state machines, Spatie Model States, state transitions, transition validation.
- `laravel-tdd` — Test-Driven Development specifically for Laravel applications using Pest PHP. Use when implementing any Laravel feature or bugfix - write the test first, watch it fail, write minimal code to pass.
- `laravel-tdd-iserter` — Test-Driven Development specifically for Laravel applications using Pest PHP. Use when implementing any Laravel feature or bugfix - write the test first, watch it fail, write minimal code to pass.
- `laravel-testing` — Comprehensive testing patterns with Pest. Use when working with tests, testing patterns, or when user mentions testing, tests, Pest, PHPUnit, mocking, factories, test patterns.
- `laravel-upgrade` — Upgrade Laravel applications one major version at a time (9→10, 10→11, 11→12). Use when user wants to upgrade their Laravel framework version. Auto-detects current version from composer.json, identifies breaking changes, and applies necessary code fixes.
- `laravel-validation` — Form request validation and comprehensive validation testing. Use when working with validation rules, form requests, validation testing, or when user mentions validation, form requests, validation rules, conditional validation, validation testing.
- `laravel-value-objects` — Immutable value objects for domain values. Use when working with domain values, immutable objects, or when user mentions value objects, immutable values, domain values, money objects, coordinate objects.
- `laravele2e-playwright` — A reusable AI agent skill for laravel:e2e-playwright.
- `magento-php-specialist` — Advanced PHP development for Magento 2 following PSR-12 and Magento coding standards. Use when writing PHP code, implementing business logic, or ensuring code quality. Masters modern PHP features, object-oriented programming, design patterns, and Magento-specific PHP practices.
- `markitdown` — Convert any document or file to Markdown using Microsoft's MarkItDown library. Use this skill when the user asks to convert a file to markdown, extract text from a document, turn a PDF/Word/Excel/PowerPoint/HTML/image/audio file into markdown, or parse document content. Supports PDF, DOCX, PPTX, XLSX, HTML, images (with OCR), audio, CSV, JSON, XML, ZIP, EPub, and more.
- `moai-lang-php` — PHP 8.3+ development specialist covering Laravel 11, Symfony 7, Eloquent ORM, and modern PHP patterns. Use when developing PHP APIs, web applications, or Laravel/Symfony projects.

- `moai-lang-php-ajbcoding` — PHP 8.4+ best practices with PHPUnit 11, Composer, PSR-12 standards, and web frameworks (Laravel, Symfony).
- `moai-lang-php-modu-ai` — PHP 8.3+ development specialist covering Laravel 11, Symfony 7, Eloquent ORM, and modern PHP patterns. Use when developing PHP APIs, web applications, or Laravel/Symfony projects.

- `php` — A reusable AI agent skill for php.
- `php-api` — PHP API development mastery - REST, GraphQL, JWT/OAuth, OpenAPI documentation
- `php-best-practices` — PHP 8.x modern patterns, PSR standards, and SOLID principles. Use when reviewing PHP code, checking type safety, auditing code quality, or ensuring PHP best practices. Triggers on "review PHP", "check PHP code", "audit PHP", or "PHP best practices".
- `php-best-practices-tree-nation` — Use this skill when the user asks to create, review, refactor, or migrate PHP backend components (controllers, services, DTOs, repositories, responses, tests) following TreeNation's Domain-Driven Design architecture.
- `php-database` — PHP database mastery - PDO, Eloquent, Doctrine, query optimization, and migrations
- `php-dayuse` — Use when building PHP applications with Symfony, Doctrine, and modern PHP 8.4+. Invoke for strict typing, PHPStan level 10, DDD patterns, PSR standards, PHPUnit tests, Elasticsearch with Elastically, and Redis/MySQL optimization.
- `php-development` — Expert guidance for PHP 8+ development with SOLID principles, PSR standards, and modern best practices
- `php-development-practicalswan` — PHP 8.0+ development — XAMPP, RESTful APIs, PDO/MySQL/MariaDB, and authentication. Use when building PHP backends, creating API endpoints, configuring XAMPP, or integrating PHP with databases.
- `php-ecosystem` — This skill should be used when the user asks to "write php", "php 8", "composer", "phpunit", "pest", "phpstan", "psalm", "psr", or works with modern PHP language patterns and configuration. Provides comprehensive modern PHP ecosystem patterns and best practices.
- `php-expert` — Expert-level PHP development with PHP 8+, Laravel, Composer, and modern best practices
- `php-expert-oimiragieo` — PHP expert including Laravel, WordPress, and Drupal development
- `php-fundamentals` — Modern PHP programming skill - master PHP 8.x syntax, OOP, type system, and Composer
- `php-g1joshi` — PHP 8+ web development with Composer, attributes, and modern frameworks. Use for .php files.
- `php-guide` — PHP language guardrails, patterns, and best practices for AI-assisted development.
Use when working with PHP files (.php), composer.json, or when the user mentions PHP.
Provides type declaration patterns, Composer conventions, PSR standards,
and testing guidelines specific to this project's coding standards.

- `php-guidelines` — A reusable AI agent skill for php-guidelines.
- `php-guidelines-from-spatie` — Describes PHP and Laravel guidelines provided by Spatie. These rules result in more maintainable, and readable code.
- `php-htlin222` — Write modern PHP with generators, SPL, and PHP 8+ features. Use for PHP development or optimization.
- `php-laravel` — Laravel framework mastery - Eloquent, Blade, APIs, queues, and Laravel 11.x ecosystem
- `php-laravel-iliaal` — Modern PHP 8.4 and Laravel patterns: architecture, Eloquent, queues, testing. Use when working with Laravel, Eloquent, Blade, artisan, PHPUnit, PHPStan, or building/testing PHP applications with frameworks. Not for PHP internals (php-src) or general PHP language discussion.
- `php-mcp-server-generator` — Generate a complete PHP Model Context Protocol server project with tools, resources, prompts, and tests using the official PHP SDK
- `php-miles990` — Modern PHP programming patterns
- `php-modern` — Master of Modern PHP (8.4-8.6+), specialized in Property Hooks, Partial Function Application, and High-Performance Engine optimization.
- `php-modern-best-practices-laravel-helper` — A reusable AI agent skill for php-modern-best-practices-&-laravel-helper.
- `php-modern-features` — Use when modern PHP features including typed properties, union types, match expressions, named arguments, attributes, enums, and patterns for writing type-safe, expressive PHP code with latest language improvements.
- `php-modernization` — PHP 8.x modernization patterns. Use when upgrading to PHP 8.2/8.3/8.4, implementing type safety, or achieving PHPStan level 10.
- `php-modernization-netresearch` — Use when upgrading to PHP 8.1+, implementing type safety, configuring PHPStan/Rector/PHP-CS-Fixer, or modernizing PHP code with enums, DTOs, and readonly properties.
- `php-olino3` — A reusable AI agent skill for php.
- `php-pro` — Use when building PHP applications with modern PHP 8.3+ features, Laravel, or Symfony frameworks. Invokes strict typing, PHPStan level 9, async patterns with Swoole, and PSR standards. Creates controllers, configures middleware, generates migrations, writes PHPUnit/Pest tests, defines typed DTOs and value objects, sets up dependency injection, and scaffolds REST/GraphQL APIs. Use when working with Eloquent, Doctrine, Composer, Psalm, ReactPHP, or any PHP API development.
- `php-pro-404kidwiz` — A reusable AI agent skill for php-pro.
- `php-pro-hainamchung` — Use when building PHP applications with modern PHP 8.3+ features, Laravel, or Symfony frameworks. Invoke for strict typing, PHPStan level 9, async patterns with Swoole, PSR standards.
- `php-pro-herdiansah` — Write idiomatic PHP code with generators, iterators, SPL data structures, and modern OOP features. Use PROACTIVELY for high-performance PHP applications.
- `php-pro-rmyndharis` — Write idiomatic PHP code with generators, iterators, SPL data structures, and modern OOP features. Use PROACTIVELY for high-performance PHP applications.
- `php-pro-sickn33` — Write idiomatic PHP code with generators, iterators, SPL data
structures, and modern OOP features. Use PROACTIVELY for high-performance PHP
applications.

- `php-pro-sidetoolco` — Write idiomatic PHP code with generators, iterators, SPL data structures, and modern OOP features. Use PROACTIVELY for high-performance PHP applications.
- `php-symfony` — Symfony framework mastery - Doctrine, DI container, Messenger, and enterprise architecture
- `php-test-writer` — Skill for creating and editing PHP tests following project conventions. Use when creating tests, updating test files, or refactoring tests. Applies proper structure, naming, factory usage, and Laravel/PHPUnit best practices.
- `php-testing` — PHP testing mastery - PHPUnit 11, Pest 3, TDD, mocking, and CI/CD integration
- `php-upgrade` — Step-by-step PHP version upgrade playbook for PHP 8.0 through 8.4+ with automated tooling. Use when the user asks to upgrade PHP to a new version, check PHP compatibility, fix deprecation warnings, run Rector for automated refactoring, audit code with PHPCompatibility, or plan a PHP migration strategy. Covers breaking changes per version, php.ini configuration updates, extension compatibility, Rector rule sets, testing strategies, and the changelog-first upgrade workflow.
- `php-wordpress` — WordPress development mastery - themes, plugins, Gutenberg blocks, and REST API
- `php74-expert` — A reusable AI agent skill for php74-expert.
- `phpcs-check-fix` — Fix PHP coding style issues using PHPCS and PHPCBF. Use this skill whenever the user mentions PHPCS, code style, coding standard, cs:fix, cs:check, PHP formatting, or asks to fix/check PHP code style. Also activate when you notice PHP files have been modified and need style compliance, or when a CI PHPCS check has failed.
- `phpstan-developer` — Build PHPStan rules, collectors, and extensions that analyze PHP code for custom errors. Use when asked to create, modify, or explain PHPStan rules, collectors, or type extensions. Triggers on requests like "write a PHPStan rule to...", "create a PHPStan rule that...", "add a PHPStan rule for...", "write a collector for...", or when working on a phpstan extension package.
- `phpstan-fixer` — Fix PHPStan static analysis errors by adding type annotations and PHPDocs.
Use when encountering PHPStan errors, type mismatches, missing type hints,
or static analysis failures. Never ignores errors without user approval.

- `phpunit-best-practices` — PHPUnit testing best practices and conventions guide. This skill should be used when writing, reviewing, or refactoring PHPUnit tests to ensure consistent, maintainable, and effective test suites. Triggers on tasks involving test creation, test refactoring, test configuration, code coverage, data providers, mocking, or PHPUnit XML configuration.
- `phpunit-skill` — Generates PHPUnit tests in PHP. Covers assertions, data providers, mocking, and test doubles. Use when user mentions "PHPUnit", "TestCase", "assertEquals", "PHP test". Triggers on: "PHPUnit", "TestCase PHP", "assertEquals PHP", "PHP unit test".

- `phpunit-testing-pro` — Senior-level PHPUnit testing skill for Laravel/PHP applications. Use PROACTIVELY when writing tests, creating test suites, mocking dependencies, testing APIs, database testing, or improving test coverage. Covers PHPUnit 10+, Laravel testing helpers, data providers, mocks/stubs, Pest PHP comparison, and testing best practices. Trigger for test creation, test debugging, coverage improvement, or any testing-related questions.
- `refactorlaravel` — A reusable AI agent skill for refactor:laravel.
- `review-php` — Review PHP code for language and runtime conventions: strict types, error handling, resource management, PSR standards, namespaces, null safety, generators, and testability. Language-only atomic skill; output is a findings list.
- `screenshot` — Capture screenshots of the desktop, a specific window, or a screen region. Use this skill when the user asks to take a screenshot, capture the screen, grab a screen image, or save what's on screen to a file.
- `self-correct-reasoning` — Analyze and correct previous responses when questioned or when contradictions are detected. Use this skill when the user challenges your reasoning, points out inconsistencies, or asks 'what makes you think that?' to help you review your logic, identify errors in your previous statements, and provide accurate corrections. Useful for maintaining consistency, admitting mistakes, and rebuilding trust through transparent self-evaluation.
- `send-email` — Send emails via SMTP or API. Use this skill when the user asks to send an email, email someone, compose and send a message via email, or notify someone by email. Supports attachments, HTML body, and multiple recipients.
- `sentry-php-sdk` — Full Sentry SDK setup for PHP. Use when asked to "add Sentry to PHP", "install sentry/sentry", "setup Sentry in PHP", or configure error monitoring, tracing, profiling, logging, metrics, or crons for PHP applications. Supports plain PHP, Laravel, and Symfony.
- `shadcn-vue` — shadcn-vue for Vue/Nuxt with Reka UI components and Tailwind. Use for accessible UI, Auto Form, data tables, charts, dark mode, MCP server setup, or encountering component imports, Reka UI errors.
- `shopware-phpunit` — Best practices for writing PHPUnit tests in Shopware 6 projects, including integration tests, unit tests, and common testing patterns for plugins and apps.
- `skill-creator` — Guide for creating effective skills. This skill should be used when users want to create a new skill (or update an existing skill) that extends Claude's capabilities with specialized knowledge, workflows, or tool integrations.
- `skill-creator-leeovery` — Guide for creating effective skills. This skill should be used when users want to create a new skill (or update an existing skill) that extends Claude's capabilities with specialized knowledge, workflows, or tool integrations.
- `solid-php` — SOLID principles for Laravel 12 and PHP 8.5. Files < 100 lines, interfaces separated, PHPDoc mandatory. Auto-detects Laravel and FuseCore architecture.
- `spatie-laravel-php-standards` — Apply Spatie's Laravel and PHP coding standards for any task that creates, edits, reviews, refactors, or formats Laravel/PHP code or Blade templates; use for controllers, Eloquent models, routes, config, validation, migrations, tests, and related files to align with Laravel conventions and PSR-12.
- `summarize-composer-json` — Analyze and summarize PHP Composer configuration files (composer.json). Use this skill when the user asks to summarize, review, analyze, or understand a composer.json file. Extracts and explains project metadata, dependencies, requirements, scripts, and configuration settings in a clear, structured format.
- `symfonytdd-with-phpunit` — A reusable AI agent skill for symfony:tdd-with-phpunit.
- `technical-debt-manager-php-laravel` — Expert technical debt analyst for PHP/Laravel code health, maintainability, and strategic refactoring planning. Use PROACTIVELY when a Laravel codebase shows complexity growth, when planning sprints, or when prioritizing engineering work.
- `template-skill` — A reusable AI agent skill for template-skill.
- `translate` — Translate text between languages. Use this skill when the user asks to translate text, convert text to another language, say something in another language, or get a translation. Supports 100+ languages with no API key required.
- `web-artifacts-builder` — Suite of tools for creating elaborate, multi-component claude.ai HTML artifacts using modern frontend web technologies (React, Tailwind CSS, shadcn/ui). Use for complex artifacts requiring state management, routing, or shadcn/ui components - not for simple single-file HTML/JSX artifacts.
- `webman-best-practices` — MUST be used for Webman framework projects. Covers DDD architecture with controller/service/domain/infrastructure layers, strict dependency rules, lowercase directory naming, PER Coding Style with declare(strict_types=1) and final classes. Use when building Webman applications, implementing domain-driven design, or working with service layer patterns.
- `word-documents` — Process, analyze, and manipulate Word documents (.docx). Use when the user wants to work with Word files: analyze structure/comments/changes, add review comments, accept/reject tracked changes, apply formatting standards, convert between formats (Markdown/DOCX/PDF/HTML), compare documents, create new documents, or merge files. Essential for legal, compliance, regulatory, and professional document workflows.
- `wp-phpstan` — Use when configuring, running, or fixing PHPStan static analysis in WordPress projects (plugins/themes/sites): phpstan.neon setup, baselines, WordPress-specific typing, and handling third-party plugin classes.
- `wp-phpstan-automattic` — Use when configuring, running, or fixing PHPStan static analysis in WordPress projects (plugins/themes/sites): phpstan.neon setup, baselines, WordPress-specific typing, and handling third-party plugin classes.
- `wp-phpstan-firecrawl` — Use when configuring, running, or fixing PHPStan static analysis in WordPress projects (plugins/themes/sites): phpstan.neon setup, baselines, WordPress-specific typing, and handling third-party plugin classes.
- `wp-phpstan-the-lemonboy` — Use when configuring, running, or fixing PHPStan static analysis in WordPress projects (plugins/themes/sites): phpstan.neon setup, baselines, WordPress-specific typing, and handling third-party plugin classes.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- This repository's approved foundation directories for request validation, actions, and support services are `app/Http/Requests`, `app/Filament/Actions`, and `app/Filament/Support`.
- Do not create `app/Actions` or `app/Support`. New request classes belong in `app/Http/Requests`, while new action and support classes belong in the Filament foundation tree.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan Commands

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`, `php artisan tinker --execute "..."`).
- Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Debugging

- Use the `database-query` tool when you only need to read from the database and Boost MCP is available in the current environment.
- Use the `database-schema` tool to inspect table structure before writing migrations or models when Boost MCP is available in the current environment.
- To execute PHP code for debugging, run `php artisan tinker --execute "your code here"` directly.
- To read configuration values, read the config files directly or run `php artisan config:show [key]`.
- To inspect routes, run `php artisan route:list` directly.
- To check environment variables, read the `.env` file directly.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost when Boost MCP is available in the current environment.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- When Boost MCP is available in the current environment, use its `search-docs` tool before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach. If Boost MCP is unavailable, fall back to the verified local docs or official framework docs.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Form Request classes must live under `App\\Filament\\Requests\\...`, not `App\\Http\\Requests\\...`.
- Check sibling Form Requests to see if the application uses array or string based validation rules.
- Shared orchestration and support code must live under `App\\Filament\\Actions\\...` and `App\\Filament\\Support\\...`.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: When Boost MCP is available, use `search-docs` for version-specific Laravel documentation and updated code examples.
- This repository currently uses the Laravel 11/12 bootstrap structure with `bootstrap/app.php`.
- Follow the existing structure in the repository instead of assuming an older Laravel 10 layout.

## Laravel 11/12 Structure

- Middleware classes typically live in `app/Http/Middleware/` and service providers in `app/Providers/`.
- Application configuration is centered in `bootstrap/app.php`:
    - route registration is configured in `bootstrap/app.php`
    - middleware aliases and web stack configuration are configured in `bootstrap/app.php`
    - exception configuration is wired in `bootstrap/app.php`
    - providers are registered through `bootstrap/providers.php`

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: When Boost MCP is available, use `search-docs` for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: When Boost MCP is available, use `search-docs` for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data alone.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.

=== filament/filament rules ===

## Filament

- Filament is used by this application. Follow the existing conventions for how and where it is implemented.
- Filament is a Server-Driven UI (SDUI) framework for Laravel that lets you define user interfaces in PHP using structured configuration objects. Built on Livewire, Alpine.js, and Tailwind CSS.
- When available, use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

### Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan --help`.
- Always inspect required options before running a command, and always pass `--no-interaction`.

### Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field visibility" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column value" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Actions encapsulate a button with an optional modal form and logic:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

Action::make('updateEmail')
    ->schema([
        TextInput::make('email')
            ->email()
            ->required(),
    ])
    ->action(fn (array $data, User $record) => $record->update($data))

</code-snippet>

### Testing

Always authenticate before testing panel functionality. Filament uses Livewire, so use `Livewire::test()` or `livewire()` (available when `pestphp/pest-plugin-livewire` is in `composer.json`):

<code-snippet name="Table test" lang="php">
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
    ->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="Create resource test" lang="php">
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertRedirect();

assertDatabaseHas(User::class, [
    'name' => 'Test',
    'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="Testing validation" lang="php">
use function Pest\Livewire\livewire;

livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();

</code-snippet>

<code-snippet name="Calling actions in pages" lang="php">
use Filament\Actions\DeleteAction;
use function Pest\Livewire\livewire;

livewire(EditUser::class, ['record' => $user->id])
    ->callAction(DeleteAction::class)
    ->assertNotified()
    ->assertRedirect();

</code-snippet>

<code-snippet name="Calling actions in tables" lang="php">
use Filament\Actions\Testing\TestAction;
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->callAction(TestAction::make('promote')->table($user), [
        'role' => 'admin',
    ])
    ->assertNotified();

</code-snippet>

### Correct Namespaces

- Form fields (`TextInput`, `Select`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

### Common Mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, and `Fieldset` do not span all columns by default. Explicitly set column spans when needed.

</laravel-boost-guidelines>

<project-skills>
=== project skill routing ===

# Tenanto Skill Matrix (Maximum Practical Coverage)

This repository already includes workspace-local skills in `.agent/skills`. For this project, use the broadest relevant set of skills below to maximize output quality while keeping routing task-specific.

## Skill Resolution Order
- Prefer workspace skills in `.agent/skills` first.
- Fall back to global Codex skills in `$CODEX_HOME/skills` when a matching workspace skill is unavailable.
- Combine multiple skills when a task crosses domains (for example security + performance + testing).
- OpenAI curated and system skills are mirrored locally in `.agent/skills` for this project (`90` workspace skills total).

## Core Skills (Default for Most Tasks)
- `architecture`
- `clean-code`
- `code-review-checklist`
- `database-design`
- `testing-patterns`
- `tdd-workflow`
- `vulnerability-scanner`
- `performance-profiling`
- `systematic-debugging`
- `i18n-localization`

## Backend / Laravel / API
- `api-patterns` for API contracts, validation, responses, versioning.
- `database-design` for schema, indexing, and migration strategy.
- `architecture` for service boundaries and refactors.
- `mcp-builder` for MCP server/tool integrations.
- `vulnerability-scanner`, `security-best-practices`, `security-threat-model`, `security-ownership-map`, and `red-team-tactics` for auth, tenant isolation, and security hardening.
- `sentry` for error monitoring, alerting, and incident triage workflows.

## Filament / Livewire / Frontend
- `frontend-design` for UI/UX decisions in Blade/Livewire/Filament.
- `tailwind-patterns` for Tailwind CSS v4 usage and token structure.
- `web-design-guidelines` for accessibility/usability audits.
- `webapp-testing` for browser-level regression checks.

## Quality / Testing / Debugging
- `testing-patterns` and `tdd-workflow` for new behavior and regression protection.
- `code-review-checklist` for review requests and risk-focused assessments.
- `lint-and-validate` for project checks before finalizing.
- `systematic-debugging` for production-like incident triage.
- `playwright`, `playwright-interactive`, and `screenshot` for deterministic browser automation and visual evidence capture.

## Planning / Communication / Documentation
- `brainstorming` for ambiguous or complex requests.
- `plan-writing` for decomposition and execution plans.
- `doc-coauthoring` for proposals/specs/technical docs.
- `internal-comms` for status updates, incident reports, stakeholder summaries.
- `brand-guidelines` when visual outputs must follow a strict brand system.

## Reporting / File Artifacts
- `pdf` for PDF generation, extraction, and manipulation.
- `xlsx` for spreadsheet/report export workflows.
- `docx` for Word documents and templates.
- `pptx` for presentation decks.
- `theme-factory` for consistent visual/theming outputs when requested.

## Tooling / Automation / Skill Maintenance
- `antigravity-cli` for `.agent` kit management.
- `skill-installer` for installing/updating Codex skills.
- `skill-creator` for adding custom project skills.
- `web-artifacts-builder` for complex, multi-component web artifacts.
- `confluence-deep-reader` when ingesting Confluence parent/child documentation.

## Extended Curated Skills (Use On Demand)
- `gh-address-comments`, `gh-fix-ci`, and `linear` for engineering workflow automation.
- `netlify-deploy`, `render-deploy`, `vercel-deploy`, and `cloudflare-deploy` for deployment workflows when needed.
- `figma` and `figma-implement-design` for design-to-implementation handoffs.
- `openai-docs` for OpenAI platform/product documentation lookups.

## Project Custom Skills
- `tenanto-laravel-stack` for repository-specific Laravel/Filament/Livewire implementation patterns.
- `tenanto-tenant-security` for tenant boundary and authorization hardening.
- `tenanto-billing-reporting` for billing, tariffs, invoices, and export/report workflows.

## Optional Visual-Media Skills (Use Only on Explicit Request)
- `canvas-design`
- `algorithmic-art`
- `slack-gif-creator`

## Tenanto Domain Routing
- Multi-tenant auth/authorization/context issues: `architecture`, `vulnerability-scanner`, `security-threat-model`, `systematic-debugging`.
- Filament resources/pages/widgets and Livewire UX: `frontend-design`, `tailwind-patterns`, `web-design-guidelines`, `webapp-testing`.
- Billing, tariffs, invoices, service calculations: `tenanto-billing-reporting`, `architecture`, `database-design`, `testing-patterns`, `tdd-workflow`.
- Security incidents and hardening: `tenanto-tenant-security`, `security-best-practices`, `security-ownership-map`, `red-team-tactics`, `sentry`.
- Performance and query bottlenecks: `performance-profiling`, `database-design`, `systematic-debugging`.
- Localization and translations: `i18n-localization`, `testing-patterns`.
- PDF/Excel/Office exports: `pdf`, `xlsx`, `docx`, `pptx`.

</project-skills>
