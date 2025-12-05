@echo off
echo ========================================
echo Subscription Test Verification
echo ========================================
echo.

echo Running Subscription Tests...
echo.
php artisan test --filter=SubscriptionTest

echo.
echo ========================================
echo Test Summary Complete
echo ========================================
echo.
echo For detailed documentation, see:
echo - docs/testing/SUBSCRIPTION_TEST_IMPROVEMENTS.md
echo - SUBSCRIPTION_TEST_REFACTORING_SUMMARY.md
echo.
pause
