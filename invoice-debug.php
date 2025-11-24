<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{Property,Tenant,Invoice,User};
use App\Enums\UserRole;
use Illuminate\Support\Facades\Artisan;

config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
Artisan::call('migrate');

$property1 = Property::factory()->create();
$tenant1 = Tenant::factory()->create(['property_id' => $property1->id]);
$invoice1 = Invoice::factory()->create([
    'tenant_renter_id' => $tenant1->id,
    'tenant_id' => $property1->tenant_id,
]);

$property2 = Property::factory()->create(['tenant_id' => $property1->tenant_id]);
$tenant2 = Tenant::factory()->create(['property_id' => $property2->id]);

$tenantUser2 = User::factory()->create([
    'role' => UserRole::TENANT,
    'email' => $tenant2->email,
    'tenant_id' => $property1->tenant_id,
]);

$policy = app(\App\Policies\InvoicePolicy::class);

echo "Tenant Record ID: " . ($tenantUser2->tenant?->id ?? 'null') . PHP_EOL;
echo "Tenant Record Email: " . ($tenantUser2->tenant?->email ?? 'null') . PHP_EOL;
echo "Invoice Renter ID: {$invoice1->tenant_renter_id}\n";
echo "Invoice Tenant ID: {$invoice1->tenant_id}\n";
echo "User Tenant ID: {$tenantUser2->tenant_id}\n";

echo "Policy view result: " . ($policy->view($tenantUser2, $invoice1) ? 'true' : 'false') . PHP_EOL;
