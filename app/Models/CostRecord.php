<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectCostRecordType;
use Database\Factories\CostRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CostRecord extends Model
{
    /** @use HasFactory<CostRecordFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'project_id',
        'created_by_user_id',
        'type',
        'description',
        'amount',
        'incurred_on',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProjectCostRecordType::class,
            'amount' => 'decimal:2',
            'incurred_on' => 'date',
            'metadata' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
