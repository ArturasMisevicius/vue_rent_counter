<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Laravel\Sanctum\Contracts\HasAbilities;

/**
 * Personal Access Token Model
 * 
 * Custom implementation that replaces Laravel Sanctum's HasApiTokens trait
 * while maintaining the same interface and functionality.
 * 
 * @property int $id
 * @property string $tokenable_type
 * @property int $tokenable_id
 * @property string $name
 * @property string $token
 * @property array $abilities
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PersonalAccessToken extends Model implements HasAbilities
{
    use HasFactory;

    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'last_used_at',
    ];

    protected $casts = [
        'abilities' => 'json',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    /**
     * Get the tokenable model (User, etc.)
     */
    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Find a token by its plain text value.
     */
    public static function findToken(string $token): ?self
    {
        if (strpos($token, '|') === false) {
            return null;
        }

        [$id, $token] = explode('|', $token, 2);

        if ($instance = static::find($id)) {
            return hash_equals($instance->token, hash('sha256', $token)) ? $instance : null;
        }

        return null;
    }

    /**
     * Determine if the token has a given ability.
     */
    public function can($ability): bool
    {
        return in_array('*', $this->abilities) ||
               array_key_exists($ability, array_flip($this->abilities));
    }

    /**
     * Determine if the token is missing a given ability.
     */
    public function cant($ability): bool
    {
        return !$this->can($ability);
    }

    /**
     * Get the token's abilities.
     */
    public function getAbilities(): array
    {
        return $this->abilities ?? [];
    }

    /**
     * Check if token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Update the last used timestamp.
     */
    public function markAsUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->save();
    }

    /**
     * Generate a new token hash.
     */
    public static function generateTokenHash(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }

    /**
     * Create a new token instance.
     */
    public static function createToken(
        Model $tokenable,
        string $name,
        array $abilities = ['*'],
        ?\DateTimeInterface $expiresAt = null
    ): array {
        $plainTextToken = Str::random(40);
        
        $token = static::create([
            'tokenable_type' => get_class($tokenable),
            'tokenable_id' => $tokenable->getKey(),
            'name' => $name,
            'token' => static::generateTokenHash($plainTextToken),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return [
            'accessToken' => $token,
            'plainTextToken' => $token->getKey() . '|' . $plainTextToken,
        ];
    }

    /**
     * Scope: Active tokens (not expired)
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope: Expired tokens
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now());
    }

    /**
     * Scope: Recently used tokens
     */
    public function scopeRecentlyUsed($query, int $days = 30)
    {
        return $query->where('last_used_at', '>=', now()->subDays($days));
    }
}