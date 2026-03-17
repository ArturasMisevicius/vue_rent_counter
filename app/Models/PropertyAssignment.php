<?php

namespace App\Models;

use Database\Factories\PropertyAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyAssignment extends Model
{
    /** @use HasFactory<PropertyAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'property_id',
        'tenant_user_id',
        'unit_area_sqm',
        'assigned_at',
        'unassigned_at',
    ];

    protected function casts(): array
    {
        return [
            'unit_area_sqm' => 'decimal:2',
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }
}
