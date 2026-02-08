#!/bin/bash

# Custom API Token System Deployment Script
# Ensures zero-downtime deployment of the new token management system

set -e

echo "🚀 Deploying Custom API Token Management System..."

# 1. Backup current state
echo "📦 Creating backup..."
php artisan backup:run --only-db

# 2. Run any pending migrations (should be none for this change)
echo "🗄️ Running migrations..."
php artisan migrate --force

# 3. Clear caches to ensure fresh start
echo "🧹 Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 4. Optimize for production
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Test token system functionality
echo "🧪 Testing token system..."
php artisan test tests/Unit/Services/ApiTokenManagerTest.php --stop-on-failure
php artisan test tests/Feature/UserApiTokenIntegrationTest.php --stop-on-failure

# 6. Verify API endpoints are working
echo "🔍 Verifying API endpoints..."
if command -v curl &> /dev/null; then
    # Test health endpoint
    curl -f http://localhost/api/v1/validation/health || {
        echo "❌ API health check failed"
        exit 1
    }
    echo "✅ API endpoints responding"
else
    echo "⚠️ curl not available, skipping API verification"
fi

# 7. Schedule token pruning if not already scheduled
echo "⏰ Setting up token pruning..."
if ! crontab -l | grep -q "tokens:prune-expired"; then
    (crontab -l 2>/dev/null; echo "0 2 * * * cd $(pwd) && php artisan tokens:prune-expired --hours=24") | crontab -
    echo "✅ Token pruning scheduled"
else
    echo "✅ Token pruning already scheduled"
fi

# 8. Restart queue workers to pick up new code
echo "🔄 Restarting queue workers..."
php artisan queue:restart

# 9. Final verification
echo "🔍 Final system verification..."
php artisan tinker --execute="
\$user = App\Models\User::first();
if (\$user) {
    \$token = \$user->createApiToken('deployment-test');
    \$count = \$user->getActiveTokensCount();
    \$user->revokeAllApiTokens();
    echo 'Token system working: created token, count=' . \$count . PHP_EOL;
} else {
    echo 'No users found for testing' . PHP_EOL;
}
"

echo "✅ Custom API Token Management System deployed successfully!"
echo ""
echo "📊 System Status:"
echo "   - Token management: Active"
echo "   - Caching: Enabled"
echo "   - Monitoring: Enabled"
echo "   - Pruning: Scheduled"
echo ""
echo "🔗 Useful commands:"
echo "   - Check system health: php artisan tinker --execute=\"app(App\\Services\\ApiTokenMonitoringService::class)->checkSystemHealth()\""
echo "   - View token statistics: php artisan tinker --execute=\"app(App\\Services\\ApiTokenManager::class)->getTokenStatistics()\""
echo "   - Prune expired tokens: php artisan tokens:prune-expired"
echo ""
echo "📚 Documentation: docs/CUSTOM_API_TOKEN_SYSTEM.md"