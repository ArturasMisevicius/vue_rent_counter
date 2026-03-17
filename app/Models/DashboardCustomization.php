<?php

namespace App\Models;

use Database\Factories\DashboardCustomizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardCustomization extends Model
{
    /** @use HasFactory<DashboardCustomizationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'widget_configuration',
        'layout_configuration',
        'refresh_intervals',
    ];

    protected function casts(): array
    {
        return [
            'widget_configuration' => 'array',
            'layout_configuration' => 'array',
            'refresh_intervals' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
