<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateToHierarchicalUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:migrate-hierarchical 
                            {--rollback : Rollback the migration changes}
                            {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing users to hierarchical user structure with subscriptions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('rollback')) {
            return $this->rollback();
        }

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Start transaction
        DB::beginTransaction();

        try {
            $this->info('Starting migration to hierarchical user structure...');
            $this->newLine();

            // Step 1: Get all users that need migration
            // Find users with manager role OR admin/manager users without tenant_id
            $users = User::where(function ($query) {
                $query->where('role', UserRole::MANAGER)
                      ->orWhere(function ($q) {
                          $q->whereIn('role', ['admin', 'manager'])
                            ->where(function ($q2) {
                                $q2->whereNull('tenant_id')
                                   ->orWhere('tenant_id', 0);
                            });
                      });
            })->get();

            if ($users->isEmpty()) {
                $this->info('No users found that need role/tenant_id migration.');
            } else {
                $this->info("Found {$users->count()} users to migrate");
            }
            $this->newLine();

            // Step 2: Get the next available tenant_id
            $maxTenantId = User::max('tenant_id') ?? 0;
            $nextTenantId = $maxTenantId + 1;

            $migratedCount = 0;
            $subscriptionCount = 0;
            $inactiveCount = 0;

            foreach ($users as $user) {
                $oldRole = $user->role->value;
                $oldTenantId = $user->tenant_id;
                $needsTenantId = empty($user->tenant_id) || $user->tenant_id == 0;

                // Assign unique tenant_id only if needed
                if ($needsTenantId) {
                    $user->tenant_id = $nextTenantId;
                }

                // Convert 'manager' role to 'admin' role
                if ($user->role === UserRole::MANAGER) {
                    $user->role = UserRole::ADMIN;
                }

                // Set is_active = true
                $user->is_active = true;

                // Set organization name if not set
                if (empty($user->organization_name)) {
                    $user->organization_name = $user->name . "'s Organization";
                }

                if (!$dryRun) {
                    $user->save();
                }

                $this->line("✓ User #{$user->id} ({$user->email}):");
                $this->line("  - Role: {$oldRole} → {$user->role->value}");
                if ($needsTenantId) {
                    $this->line("  - Tenant ID: " . ($oldTenantId ?: 'null') . " → {$user->tenant_id}");
                } else {
                    $this->line("  - Tenant ID: {$user->tenant_id} (unchanged)");
                }
                $this->line("  - Organization: {$user->organization_name}");
                $this->line("  - Active: true");

                // Step 3: Create default active subscription for admin users if they don't have one
                if ($user->role === UserRole::ADMIN && !$user->subscription) {
                    $subscription = new Subscription([
                        'user_id' => $user->id,
                        'plan_type' => 'professional',
                        'status' => 'active',
                        'starts_at' => now(),
                        'expires_at' => now()->addYear(),
                        'max_properties' => 50,
                        'max_tenants' => 200,
                    ]);

                    if (!$dryRun) {
                        $subscription->save();
                    }

                    $this->line("  - Subscription: Professional plan (expires " . $subscription->expires_at->format('Y-m-d') . ")");
                    $subscriptionCount++;
                }

                $this->newLine();
                $migratedCount++;
                
                // Only increment tenant_id if we assigned a new one
                if ($needsTenantId) {
                    $nextTenantId++;
                }
            }

            // Step 4: Set is_active = true for all existing users if not already set
            $inactiveUsersQuery = User::where(function ($query) {
                $query->where('is_active', false)
                      ->orWhereNull('is_active');
            });
            
            $inactiveUsers = $inactiveUsersQuery->get();
            $inactiveCount = $inactiveUsers->count();
            
            if ($inactiveUsers->isNotEmpty()) {
                $this->info("Setting is_active = true for {$inactiveCount} users...");
                
                foreach ($inactiveUsers as $user) {
                    $user->is_active = true;
                    if (!$dryRun) {
                        $user->save();
                    }
                    $this->line("✓ User #{$user->id} ({$user->email}) set to active");
                }
                $this->newLine();
            }
            
            // Step 5: Create subscriptions for admin users who don't have one
            $adminsWithoutSubscription = User::where('role', UserRole::ADMIN)
                ->whereDoesntHave('subscription')
                ->get();
            
            if ($adminsWithoutSubscription->isNotEmpty()) {
                $this->info("Creating subscriptions for {$adminsWithoutSubscription->count()} admin users...");
                
                foreach ($adminsWithoutSubscription as $admin) {
                    $subscription = new Subscription([
                        'user_id' => $admin->id,
                        'plan_type' => 'professional',
                        'status' => 'active',
                        'starts_at' => now(),
                        'expires_at' => now()->addYear(),
                        'max_properties' => 50,
                        'max_tenants' => 200,
                    ]);

                    if (!$dryRun) {
                        $subscription->save();
                    }

                    $this->line("✓ User #{$admin->id} ({$admin->email}) - Subscription created");
                    $subscriptionCount++;
                }
                $this->newLine();
            }

            if ($dryRun) {
                $this->warn('DRY RUN COMPLETE - No changes were made');
                DB::rollback();
            } else {
                DB::commit();
                $this->info('Migration completed successfully!');
            }

            $this->newLine();
            $this->info("Summary:");
            $this->line("  - Users migrated: {$migratedCount}");
            $this->line("  - Subscriptions created: {$subscriptionCount}");
            $this->line("  - Inactive users activated: {$inactiveCount}");

            return self::SUCCESS;

        } catch (\Exception $e) {
            DB::rollback();
            $this->error('Migration failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }

    /**
     * Rollback the migration changes.
     */
    protected function rollback(): int
    {
        $this->warn('Rolling back hierarchical user migration...');
        $this->newLine();

        if (!$this->confirm('This will revert admin roles back to manager and remove subscriptions. Continue?')) {
            $this->info('Rollback cancelled.');
            return self::SUCCESS;
        }

        DB::beginTransaction();

        try {
            // Step 1: Delete all subscriptions
            $subscriptionCount = Subscription::count();
            Subscription::truncate();
            $this->info("✓ Deleted {$subscriptionCount} subscriptions");

            // Step 2: Revert admin roles back to manager
            $admins = User::where('role', UserRole::ADMIN)->get();
            $revertedCount = 0;

            foreach ($admins as $admin) {
                $admin->role = UserRole::MANAGER;
                $admin->tenant_id = null;
                $admin->organization_name = null;
                $admin->save();
                $revertedCount++;
            }

            $this->info("✓ Reverted {$revertedCount} admin users back to manager role");

            // Step 3: Clear tenant_id from all users
            User::whereNotNull('tenant_id')->update(['tenant_id' => null]);
            $this->info("✓ Cleared tenant_id from all users");

            DB::commit();

            $this->newLine();
            $this->info('Rollback completed successfully!');

            return self::SUCCESS;

        } catch (\Exception $e) {
            DB::rollback();
            $this->error('Rollback failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
