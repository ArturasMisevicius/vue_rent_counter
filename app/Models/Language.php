<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'native_name',
        'is_default',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_default' => 'bool',
        'is_active' => 'bool',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
