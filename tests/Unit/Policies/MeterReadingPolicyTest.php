<?php

declare(strict_types=1);

use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use App\Policies\MeterReadingPolicy;
use App\Services\TenantBoundaryService;

beforeEach(function () {
    $this->tenantBoundaryService = app(TenantBoundaryService::class);
    $this->policy = new MeterReadingPolicy($this->tenantBoundaryService);
});

describe('MeterReadingPolicy', function () {
    describe('viewAny', function () {
        it('allows managers to view any meter readings in their tenant', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('manager');
            $this->actingAs($user);
            
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows admins to view any meter readings in their tenant', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('admin');
            $this->actingAs($user);
            
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('denies tenants from viewing any meter readings', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('tenant');
            $this->actingAs($user);
            
            expect($this->policy->viewAny($user))->toBeFalse();
        });

        it('denies users without tenant access', function () {
            $user = User::factory()->create(['tenant_id' => null]);
            $user->assignRole('manager');
            
            expect($this->policy->viewAny($user))->toBeFalse();
        });
    });

    describe('view', function () {
        it('allows manager to view meter reading in their tenant', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('manager');
            
            $meterReading = MeterReading::factory()->create(['tenant_id' => 100]);
            
            expect($this->policy->view($user, $meterReading))->toBeTrue();
        });

        it('denies manager from viewing meter reading in different tenant', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('manager');
            
            $meterReading = MeterReading::factory()->create(['tenant_id' => 200]);
            
            expect($this->policy->view($user, $meterReading))->toBeFalse();
        });

        it('allows superadmin to view any meter reading', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('superadmin');
            
            $meterReading = MeterReading::factory()->create(['tenant_id' => 200]);
            
            expect($this->policy->view($user, $meterReading))->toBeTrue();
        });

        it('allows tenant to view meter reading for their property', function () {
            $property = Property::factory()->create(['tenant_id' => 100]);
            $user = User::factory()->create([
                'tenant_id' => 100,
                'property_id' => $property->id
            ]);
            $user->assignRole('tenant');
            
            $meter = \App\Models\Meter::factory()->create(['property_id' => $property->id]);
            $meterReading = MeterReading::factory()->create([
                'tenant_id' => 100,
                'meter_id' => $meter->id
            ]);
            
            expect($this->policy->view($user, $meterReading))->toBeTrue();
        });

        it('denies tenant from viewing meter reading for different property', function () {
            $property1 = Property::factory()->create(['tenant_id' => 100]);
            $property2 = Property::factory()->create(['tenant_id' => 100]);
            
            $user = User::factory()->create([
                'tenant_id' => 100,
                'property_id' => $property1->id
            ]);
            $user->assignRole('tenant');
            
            $meter = \App\Models\Meter::factory()->create(['property_id' => $property2->id]);
            $meterReading = MeterReading::factory()->create([
                'tenant_id' => 100,
                'meter_id' => $meter->id
            ]);
            
            expect($this->policy->view($user, $meterReading))->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows manager to create meter readings', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('manager');
            $this->actingAs($user);
            
            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows admin to create meter readings', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('admin');
            $this->actingAs($user);
            
            expect($this->policy->create($user))->toBeTrue();
        });

        it('denies tenant from creating meter readings', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('tenant');
            $this->actingAs($user);
            
            expect($this->policy->create($user))->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows manager to update non-finalized meter reading in their tenant', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('manager');
            
            $meterReading = MeterReading::factory()->create([
                'tenant_id' => 100,
                'is_finalized' => false
            ]);
            
            $result = $this->policy->update($user, $meterReading);
            expect($result->allowed())->toBeTrue();
        });

        it('denies updating finalized meter reading', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('manager');
            
            $meterReading = MeterReading::factory()->create([
                'tenant_id' => 100,
                'is_finalized' => true
            ]);
            
            $result = $this->policy->update($user, $meterReading);
            expect($result->denied())->toBeTrue();
            expect($result->message())->toContain('finalized');
        });

        it('denies updating meter reading from different tenant', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('manager');
            
            $meterReading = MeterReading::factory()->create([
                'tenant_id' => 200,
                'is_finalized' => false
            ]);
            
            $result = $this->policy->update($user, $meterReading);
            expect($result->denied())->toBeTrue();
        });
    });

    describe('delete', function () {
        it('allows admin to delete non-finalized meter reading in their tenant', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('admin');
            
            $meterReading = MeterReading::factory()->create([
                'tenant_id' => 100,
                'is_finalized' => false
            ]);
            
            $result = $this->policy->delete($user, $meterReading);
            expect($result->allowed())->toBeTrue();
        });

        it('denies manager from deleting meter reading', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('manager');
            
            $meterReading = MeterReading::factory()->create([
                'tenant_id' => 100,
                'is_finalized' => false
            ]);
            
            $result = $this->policy->delete($user, $meterReading);
            expect($result->denied())->toBeTrue();
        });

        it('denies deleting finalized meter reading', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('admin');
            
            $meterReading = MeterReading::factory()->create([
                'tenant_id' => 100,
                'is_finalized' => true
            ]);
            
            $result = $this->policy->delete($user, $meterReading);
            expect($result->denied())->toBeTrue();
            expect($result->message())->toContain('finalized');
        });
    });

    describe('finalize', function () {
        it('allows manager to finalize non-finalized meter reading in their tenant', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('manager');
            
            $meterReading = MeterReading::factory()->create([
                'tenant_id' => 100,
                'is_finalized' => false
            ]);
            
            $result = $this->policy->finalize($user, $meterReading);
            expect($result->allowed())->toBeTrue();
        });

        it('denies finalizing already finalized meter reading', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('manager');
            
            $meterReading = MeterReading::factory()->create([
                'tenant_id' => 100,
                'is_finalized' => true
            ]);
            
            $result = $this->policy->finalize($user, $meterReading);
            expect($result->denied())->toBeTrue();
            expect($result->message())->toContain('already finalized');
        });

        it('denies tenant from finalizing meter reading', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('tenant');
            
            $meterReading = MeterReading::factory()->create([
                'tenant_id' => 100,
                'is_finalized' => false
            ]);
            
            $result = $this->policy->finalize($user, $meterReading);
            expect($result->denied())->toBeTrue();
        });
    });

    describe('forceDelete', function () {
        it('allows superadmin to force delete any meter reading', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('superadmin');
            
            $meterReading = MeterReading::factory()->create(['tenant_id' => 200]);
            
            expect($this->policy->forceDelete($user, $meterReading))->toBeTrue();
        });

        it('denies admin from force deleting meter reading', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('admin');
            
            $meterReading = MeterReading::factory()->create(['tenant_id' => 100]);
            
            expect($this->policy->forceDelete($user, $meterReading))->toBeFalse();
        });
    });

    describe('bulkUpdate', function () {
        it('allows manager to bulk update meter readings', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('manager');
            $this->actingAs($user);
            
            expect($this->policy->bulkUpdate($user))->toBeTrue();
        });

        it('denies tenant from bulk updating meter readings', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('tenant');
            $this->actingAs($user);
            
            expect($this->policy->bulkUpdate($user))->toBeFalse();
        });
    });
});