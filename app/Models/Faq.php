<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
        'category',
        'display_order',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'bool',
    ];

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
