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
        'key',
        'category',
        'label',
        'description',
        'type',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'category' => SystemSettingCategory::class,
        ];
    }
}
