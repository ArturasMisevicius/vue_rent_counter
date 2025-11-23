<?php

namespace App\Models;

use App\Services\TranslationPublisher;
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

    protected $casts = [
        'values' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => app(TranslationPublisher::class)->publish());
        static::deleted(fn () => app(TranslationPublisher::class)->publish());
    }
}
