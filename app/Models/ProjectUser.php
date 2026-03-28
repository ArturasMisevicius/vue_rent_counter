<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProjectUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProjectUser extends Model
{
    /** @use HasFactory<ProjectUserFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'invited_at',
        'invited_by',
    ];

    protected function casts(): array
    {
        return [
            'invited_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
