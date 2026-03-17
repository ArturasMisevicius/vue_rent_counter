<?php

namespace App\Models;

use App\Enums\LanguageStatus;
use Database\Factories\LanguageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    /** @use HasFactory<LanguageFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'native_name',
        'status',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'status' => LanguageStatus::class,
            'is_default' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'code',
                'name',
                'native_name',
                'status',
                'is_default',
            ])
            ->where('status', LanguageStatus::ACTIVE);
    }
}
