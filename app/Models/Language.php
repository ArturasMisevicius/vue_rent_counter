<?php

namespace App\Models;

use App\Enums\LanguageStatus;
use Database\Factories\LanguageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'locale', 'code');
    }

    public function scopeForSuperadminResource(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'code',
                'name',
                'native_name',
                'status',
                'is_default',
                'created_at',
            ])
            ->withExists('users');
    }

    public function canBeDeleted(): bool
    {
        if ($this->is_default) {
            return false;
        }

        $usersExist = $this->getAttribute('users_exists');

        if ($usersExist !== null) {
            return ! (bool) $usersExist;
        }

        return ! $this->users()->exists();
    }

    public function canBeDeactivated(): bool
    {
        return ! $this->is_default;
    }
}
