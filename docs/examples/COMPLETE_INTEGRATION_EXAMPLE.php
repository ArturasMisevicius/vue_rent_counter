<?php

/**
 * Complete Integration Example
 * 
 * This file demonstrates how to use all advanced relationship patterns
 * in a real-world scenario within the Vilnius Utilities Billing system.
 */

namespace App\Examples;

use App\Models\{Invoice, Property, Meter, Building, Tenant, User, Tag, Comment, Attachment};
use App\Enums\{InvoiceStatus, UserRole};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompleteIntegrationExample
{
    /**
     * Example 1: Complete Invoice Workflow with All Relationships
     */
    public function invoiceWorkflowExample()
    {
        DB::transaction(function () {
            // 1. Create an invoice
            $invoice = Invoice::create([
                'tenant_id' => auth()->user()->tenant_id,
                'tenant_renter_id' => 1,
                'billing_period_start' => now()->startOfMonth(),
                'billing_period_end' => now()->endOfMonth(),
                'total_amount' => 150.00,
                'status' => InvoiceStatus::DRAFT,
            ]);
            
            // Activity is automatically logged via HasActivities trait
            
            // 2. Add internal comment about the invoice
            $invoice->addComment(
                body: 'Invoice created for December billing period. Includes heating charges.',
                userId: auth()->id(),
                isInternal: true
            );
            
            // 3. Tag the invoice
            $urgentTag = Tag::firstOrCreate([
                'tenant_id' => auth()->user()->tenant_id,
                'slug' => 'urgent',
            ], [
                'name' => 'Urgent',
                'color' => '#ff0000',
            ]);
            
            $invoice->attachTags([$urgentTag], taggedBy: auth()->id());
            
            // 4. Attach a supporting document
            // Simulating file upload
            $file = UploadedFile::fake()->create('invoice_details.pdf', 100);
            $invoice->attachFile(
                file: $file,
                uploadedBy: auth()->id(),
                description: 'Detailed breakdown of charges'
            );
            
            // 5. Finalize the invoice
            $invoice->finalize();
            // This triggers an activity log automatically
            
            // 6. Add a public comment for the tenant
            $invoice->addComment(
                body: 'Your invoice is ready. Payment is due by ' . $invoice->due_date->format('Y-m-d'),
                userId: auth()->id(),
                isInternal: false
            );
            
            return $invoice;
        });
    }

    /**
     * Example 2: Property Management with Tenant Assignment
     */
    public function propertyManagementExample()
    {
        // 1. Create a property
        $property = Property::create([
            'tenant_id' => auth()->user()->tenant_id,
            'address' => '123 Main St, Apt 4B',
            'type' => \App\Enums\PropertyType::APARTMENT,
            'area_sqm' => 75.5,
            'unit_number' => '4B',
            'building_id' => 1,
        ]);
        
        // 2. Tag the property
        $property->attachTags(['available', 'renovated']);
        
        // 3. Add photos
        $photos = [
            UploadedFile::fake()->image('living_room.jpg'),
            UploadedFile::fake()->image('kitchen.jpg'),
            UploadedFile::fake()->image('bedroom.jpg'),
        ];
        
        foreach ($photos as $photo) {
            $property->attachFile(
                file: $photo,
                uploadedBy: auth()->id(),
                description: 'Property photo'
            );
        }
        
        // 4. Assign a tenant with detailed pivot data
        $tenant = Tenant::find(1);
        $property->tenants()->attach($tenant->id, [
            'assigned_at' => now(),
            'monthly_rent' => 500.00,
            'deposit_amount' => 1000.00,
            'lease_type' => 'standard',
            'notes' => 'First-time tenant, requires orientation',
            'assigned_by' => auth()->id(),
        ]);
        
        // 5. Add a comment about the assignment
        $property->addComment(
            body: "Tenant {$tenant->name} assigned to property. Move-in date: " . now()->format('Y-m-d'),
            userId: auth()->id(),
            isInternal: true
        );
        
        // 6. Update tags after assignment
        $property->detachTags(['available']);
        $property->attachTags(['occupied']);
        
        return $property;
    }

    /**
     * Example 3: Meter Maintenance Workflow
     */
    public function meterMaintenanceExample()
    {
        $meter = Meter::find(1);
        
        // 1. Tag meter for maintenance
        $meter->attachTags(['maintenance-required', 'calibration-due']);
        
        // 2. Add internal comment
        $meter->addComment(
            body: 'Meter showing inconsistent readings. Schedule calibration.',
            userId: auth()->id(),
            isInternal: true
        );
        
        // 3. Attach calibration certificate
        $certificate = UploadedFile::fake()->create('calibration_cert.pdf', 50);
        $meter->attachFile(
            file: $certificate,
            uploadedBy: auth()->id(),
            description: 'Calibration certificate from 2024-11-15'
        );
        
        // 4. Log custom activity
        $meter->logActivity(
            description: 'Meter calibration completed',
            event: 'calibrated',
            properties: [
                'calibrated_by' => 'TechCorp Services',
                'calibration_date' => now()->toDateString(),
                'next_calibration' => now()->addYear()->toDateString(),
            ]
        );
        
        // 5. Update tags after maintenance
        $meter->detachTags(['maintenance-required', 'calibration-due']);
        $meter->attachTags(['calibrated', 'operational']);
        
        // 6. Add completion comment
        $meter->addComment(
            body: 'Calibration completed successfully. Next calibration due: ' . now()->addYear()->format('Y-m-d'),
            userId: auth()->id(),
            isInternal: true
        );
        
        return $meter;
    }

    /**
     * Example 4: Complex Query - Dashboard Statistics
     */
    public function dashboardStatisticsExample()
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Get comprehensive statistics with all relationships
        $stats = [
            // Invoices with comment and attachment counts
            'invoices' => Invoice::where('tenant_id', $tenantId)
                ->withCount(['comments', 'attachments', 'tags'])
                ->with(['tags', 'tenant'])
                ->latest()
                ->limit(10)
                ->get(),
            
            // Properties with active tenants and their details
            'properties' => Property::where('tenant_id', $tenantId)
                ->with([
                    'tenants' => function ($query) {
                        $query->wherePivotNull('vacated_at');
                    },
                    'tenants.pivot.assignedBy',
                    'meters.readings' => function ($query) {
                        $query->latest()->limit(1);
                    },
                ])
                ->withCount(['comments', 'attachments'])
                ->get(),
            
            // Recent activities across all models
            'recent_activities' => \App\Models\Activity::where('tenant_id', $tenantId)
                ->with(['subject', 'causer'])
                ->latest()
                ->limit(20)
                ->get(),
            
            // Popular tags
            'popular_tags' => Tag::where('tenant_id', $tenantId)
                ->popular(10)
                ->get(),
            
            // Urgent items (properties and invoices with urgent tag)
            'urgent_properties' => Property::where('tenant_id', $tenantId)
                ->withTag('urgent')
                ->with(['comments' => function ($query) {
                    $query->latest()->limit(3);
                }])
                ->get(),
            
            'urgent_invoices' => Invoice::where('tenant_id', $tenantId)
                ->withTag('urgent')
                ->with(['comments' => function ($query) {
                    $query->latest()->limit(3);
                }])
                ->get(),
            
            // Items needing attention (with recent comments)
            'items_with_recent_comments' => [
                'invoices' => Invoice::where('tenant_id', $tenantId)
                    ->whereHas('comments', function ($query) {
                        $query->where('created_at', '>=', now()->subDays(7));
                    })
                    ->with(['comments' => function ($query) {
                        $query->latest()->limit(5);
                    }])
                    ->get(),
                
                'properties' => Property::where('tenant_id', $tenantId)
                    ->whereHas('comments', function ($query) {
                        $query->where('created_at', '>=', now()->subDays(7));
                    })
                    ->with(['comments' => function ($query) {
                        $query->latest()->limit(5);
                    }])
                    ->get(),
            ],
        ];
        
        return $stats;
    }

    /**
     * Example 5: Nested Comments (Discussion Thread)
     */
    public function discussionThreadExample()
    {
        $invoice = Invoice::find(1);
        
        // 1. Initial comment
        $mainComment = $invoice->addComment(
            body: 'There seems to be an error in the heating charges calculation.',
            userId: auth()->id(),
            isInternal: false
        );
        
        // 2. Admin reply
        $adminUser = User::where('role', UserRole::ADMIN)->first();
        $adminReply = Comment::create([
            'tenant_id' => $invoice->tenant_id,
            'commentable_id' => $invoice->id,
            'commentable_type' => Invoice::class,
            'parent_id' => $mainComment->id,
            'user_id' => $adminUser->id,
            'body' => 'Thank you for reporting this. We are reviewing the calculation.',
            'is_internal' => false,
        ]);
        
        // 3. Internal note (not visible to tenant)
        Comment::create([
            'tenant_id' => $invoice->tenant_id,
            'commentable_id' => $invoice->id,
            'commentable_type' => Invoice::class,
            'parent_id' => $mainComment->id,
            'user_id' => $adminUser->id,
            'body' => 'Need to check the hot water circulation calculation for this period.',
            'is_internal' => true,
        ]);
        
        // 4. Resolution comment
        $resolutionComment = Comment::create([
            'tenant_id' => $invoice->tenant_id,
            'commentable_id' => $invoice->id,
            'commentable_type' => Invoice::class,
            'parent_id' => $mainComment->id,
            'user_id' => $adminUser->id,
            'body' => 'The calculation has been corrected. Updated invoice attached.',
            'is_internal' => false,
        ]);
        
        // 5. Attach corrected invoice
        $correctedInvoice = UploadedFile::fake()->create('corrected_invoice.pdf', 100);
        $invoice->attachFile(
            file: $correctedInvoice,
            uploadedBy: $adminUser->id,
            description: 'Corrected invoice with updated heating charges'
        );
        
        // 6. Pin the resolution comment
        $resolutionComment->is_pinned = true;
        $resolutionComment->save();
        
        // 7. Retrieve the full discussion thread
        $thread = $invoice->topLevelComments()
            ->with(['replies.user', 'replies.replies.user'])
            ->get();
        
        return $thread;
    }

    /**
     * Example 6: Bulk Operations with Relationships
     */
    public function bulkOperationsExample()
    {
        $tenantId = auth()->user()->tenant_id;
        
        // 1. Tag all overdue invoices
        $overdueTag = Tag::firstOrCreate([
            'tenant_id' => $tenantId,
            'slug' => 'overdue',
        ], [
            'name' => 'Overdue',
            'color' => '#ff0000',
        ]);
        
        $overdueInvoices = Invoice::where('tenant_id', $tenantId)
            ->where('due_date', '<', now())
            ->where('status', '!=', InvoiceStatus::PAID)
            ->get();
        
        foreach ($overdueInvoices as $invoice) {
            $invoice->attachTags([$overdueTag]);
            
            // Add automated comment
            $invoice->addComment(
                body: 'This invoice is now overdue. Please arrange payment as soon as possible.',
                userId: auth()->id(),
                isInternal: false
            );
            
            // Log activity
            $invoice->logActivity(
                description: 'Invoice marked as overdue',
                event: 'overdue',
                properties: [
                    'due_date' => $invoice->due_date->toDateString(),
                    'days_overdue' => now()->diffInDays($invoice->due_date),
                ]
            );
        }
        
        // 2. Tag all properties needing maintenance
        $maintenanceTag = Tag::firstOrCreate([
            'tenant_id' => $tenantId,
            'slug' => 'maintenance',
        ], [
            'name' => 'Maintenance',
            'color' => '#ffa500',
        ]);
        
        $propertiesNeedingMaintenance = Property::where('tenant_id', $tenantId)
            ->whereHas('comments', function ($query) {
                $query->where('body', 'like', '%maintenance%')
                    ->orWhere('body', 'like', '%repair%');
            })
            ->get();
        
        foreach ($propertiesNeedingMaintenance as $property) {
            $property->attachTags([$maintenanceTag]);
        }
        
        return [
            'overdue_invoices_tagged' => $overdueInvoices->count(),
            'properties_tagged_for_maintenance' => $propertiesNeedingMaintenance->count(),
        ];
    }

    /**
     * Example 7: Activity Log Analysis
     */
    public function activityLogAnalysisExample()
    {
        $tenantId = auth()->user()->tenant_id;
        
        // 1. Get all invoice-related activities
        $invoiceActivities = \App\Models\Activity::where('tenant_id', $tenantId)
            ->where('subject_type', Invoice::class)
            ->with(['subject', 'causer'])
            ->latest()
            ->get();
        
        // 2. Group activities by event type
        $activitiesByEvent = $invoiceActivities->groupBy('event');
        
        // 3. Get most active users
        $mostActiveUsers = \App\Models\Activity::where('tenant_id', $tenantId)
            ->where('causer_type', User::class)
            ->select('causer_id', DB::raw('count(*) as activity_count'))
            ->groupBy('causer_id')
            ->orderBy('activity_count', 'desc')
            ->limit(10)
            ->with('causer')
            ->get();
        
        // 4. Get recent changes to specific invoice
        $invoice = Invoice::find(1);
        $invoiceHistory = $invoice->activities()
            ->where('event', 'updated')
            ->get()
            ->map(function ($activity) {
                return [
                    'date' => $activity->created_at,
                    'user' => $activity->causer->name,
                    'changes' => $activity->getChanges(),
                    'old_values' => $activity->getOldValues(),
                ];
            });
        
        return [
            'total_activities' => $invoiceActivities->count(),
            'by_event' => $activitiesByEvent->map->count(),
            'most_active_users' => $mostActiveUsers,
            'invoice_history' => $invoiceHistory,
        ];
    }

    /**
     * Example 8: Search and Filter with Multiple Relationships
     */
    public function advancedSearchExample(array $filters)
    {
        $query = Property::where('tenant_id', auth()->user()->tenant_id);
        
        // Filter by tags
        if (!empty($filters['tags'])) {
            $query->withAnyTag($filters['tags']);
        }
        
        // Filter by comment keywords
        if (!empty($filters['comment_keyword'])) {
            $query->whereHas('comments', function ($q) use ($filters) {
                $q->where('body', 'like', "%{$filters['comment_keyword']}%");
            });
        }
        
        // Filter by attachment type
        if (!empty($filters['has_images'])) {
            $query->whereHas('images');
        }
        
        // Filter by recent activity
        if (!empty($filters['recent_activity_days'])) {
            $query->whereHas('activities', function ($q) use ($filters) {
                $q->where('created_at', '>=', now()->subDays($filters['recent_activity_days']));
            });
        }
        
        // Filter by tenant assignment status
        if (!empty($filters['occupancy_status'])) {
            if ($filters['occupancy_status'] === 'occupied') {
                $query->whereHas('tenants');
            } else {
                $query->doesntHave('tenants');
            }
        }
        
        // Eager load all relationships
        $results = $query->with([
            'building',
            'tenants.pivot',
            'meters',
            'comments' => function ($q) {
                $q->latest()->limit(5);
            },
            'attachments',
            'tags',
            'activities' => function ($q) {
                $q->latest()->limit(10);
            },
        ])
        ->withCount(['comments', 'attachments', 'tags'])
        ->paginate(20);
        
        return $results;
    }
}
