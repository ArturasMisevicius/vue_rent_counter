<?php

namespace App\Models;

use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'service_type',
        'contact_info',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_type' => ServiceType::class,
            'contact_info' => 'array',
        ];
    }

    /**
     * Get the tariffs for this provider.
     */
    public function tariffs(): HasMany
    {
        return $this->hasMany(Tariff::class);
    }
}
