<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'values',
    ];

    protected function casts(): array
    {
        return [
            'values' => 'array',
        ];
    }

    public function scopeForGroup(Builder $query, string $group): Builder
    {
        return $query
            ->select([
                'id',
                'group',
                'key',
                'values',
                'created_at',
                'updated_at',
            ])
            ->where('group', $group)
            ->orderBy('key');
    }
}
