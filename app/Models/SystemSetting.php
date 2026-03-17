<?php

namespace App\Models;

use App\Enums\SystemSettingCategory;
use Database\Factories\SystemSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    /** @use HasFactory<SystemSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'category',
        'key',
        'label',
        'value',
        'is_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'category' => SystemSettingCategory::class,
            'value' => 'array',
            'is_encrypted' => 'boolean',
        ];
    }
}
