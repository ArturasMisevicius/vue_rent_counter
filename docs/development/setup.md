# Development Environment Setup

## Prerequisites

- PHP 8.4+
- Composer
- Node.js 18+
- PostgreSQL 14+
- Redis 6+

## Installation

### 1. Clone Repository
```bash
git clone <repository-url>
cd cflow
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 5. Build Assets
```bash
npm run dev
```

## Development Commands

### Start Development Server
```bash
composer dev  # Starts all services (server, queue, logs, vite)
```

### Individual Services
```bash
php artisan serve          # Web server
php artisan queue:work      # Queue worker
npm run dev                 # Vite dev server
```

### Code Quality
```bash
composer lint              # Run Rector + Pint
composer test              # Run test suite
composer test:coverage     # Run tests with coverage
vendor/bin/phpstan         # Static analysis
```

## IDE Configuration

### VS Code Extensions
- PHP Intelephense
- Laravel Extension Pack
- Tailwind CSS IntelliSense
- Alpine.js IntelliSense

### PHPStorm Plugins
- Laravel Plugin
- PHP Annotations
- Tailwind CSS

## Development Workflow

1. **Create Feature Branch**
   ```bash
   git checkout -b feature/new-feature
   ```

2. **Write Tests First** (TDD)
   ```bash
   php artisan test --filter=NewFeatureTest
   ```

3. **Implement Feature**
   - Follow coding standards
   - Use Action classes for business logic
   - Add proper type hints

4. **Run Quality Checks**
   ```bash
   composer lint
   composer test
   ```

5. **Commit Changes**
   ```bash
   git add .
   git commit -m "feat: add new feature"
   ```

## Debugging

### Laravel Telescope
Access at `/telescope` in development

### Debug Bar
Enabled automatically in development

### Logging
```php
Log::debug('Debug message', ['context' => $data]);
```

## Testing

### Run All Tests
```bash
php artisan test
```

### Run Specific Tests
```bash
php artisan test --filter=UserTest
php artisan test tests/Feature/UserTest.php
```

### Generate Coverage Report
```bash
php artisan test --coverage
```

## Common Issues

### Permission Issues
```bash
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Clear Caches
```bash
php artisan optimize:clear
```

### Reset Database
```bash
php artisan migrate:fresh --seed
```

## Related Documentation

- [Coding Standards](./standards.md)
- [Testing Guidelines](../testing/overview.md)
- [Architecture Overview](../architecture/overview.md)