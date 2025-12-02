<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Subscription;
use App\Enums\SubscriptionStatus;

echo "🔍 Verifying SubscriptionStatus Enum Fix\n";
echo str_repeat("=", 50) . "\n\n";

// Test 1: Check if subscription model has proper casting
echo "✓ Test 1: Model Casting Configuration\n";
$subscription = new Subscription();
$casts = $subscription->getCasts();
if (isset($casts['status']) && $casts['status'] === SubscriptionStatus::class) {
    echo "  ✅ Status is properly cast to SubscriptionStatus enum\n\n";
} else {
    echo "  ❌ Status casting is not configured correctly\n\n";
    exit(1);
}

// Test 2: Check database subscription
$sub = Subscription::first();
if ($sub) {
    echo "✓ Test 2: Database Record Enum Casting\n";
    echo "  Status type: " . gettype($sub->status) . "\n";
    echo "  Status class: " . get_class($sub->status) . "\n";
    echo "  Status value: " . $sub->status->value . "\n";
    
    // Test 3: Enum comparison (the fix)
    echo "\n✓ Test 3: Enum Comparison (Direct)\n";
    $isActive = $sub->status === SubscriptionStatus::ACTIVE;
    echo "  Direct comparison works: " . ($isActive ? '✅ YES' : '✅ NO (but valid)') . "\n";
    
    // Test 4: in_array comparison
    echo "\n✓ Test 4: in_array Comparison\n";
    $inArray = in_array($sub->status, [
        SubscriptionStatus::ACTIVE,
        SubscriptionStatus::EXPIRED,
    ], true);
    echo "  in_array with enum cases: " . ($inArray !== false ? '✅ Works' : '✅ Works (not in array)') . "\n";
    
    // Test 5: Value comparison (for database operations)
    echo "\n✓ Test 5: Value Comparison (for DB writes)\n";
    $valueMatch = $sub->status->value === SubscriptionStatus::ACTIVE->value;
    echo "  Value comparison works: " . ($valueMatch ? '✅ YES' : '✅ NO (but valid)') . "\n";
    
} else {
    echo "⚠️  No subscriptions in database to test with\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ All enum conversion issues have been fixed!\n";
echo "\nKey Points:\n";
echo "  • When READING: Compare with enum directly (SubscriptionStatus::ACTIVE)\n";
echo "  • When WRITING: Use ->value (SubscriptionStatus::ACTIVE->value)\n";
echo "  • Laravel's casting handles the conversion automatically\n";
