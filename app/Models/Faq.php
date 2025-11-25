<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * FAQ Model
 *
 * Security features:
 * - HTML sanitization on answer field
 * - Audit trail for all changes
 * - Soft deletes enabled
 * - Mass assignment protection
 *
 * @property int $id
 * @property string $question
 * @property string $answer
 * @property string|null $category
 * @property int $display_order
 * @property bool $is_published
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class Faq extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * Note: display_order and is_published are intentionally excluded
     * to prevent unauthorized manipulation.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'question',
        'answer',
        'category',
    ];

    /**
     * The attributes that should be guarded from mass assignment.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'display_order',
        'is_published',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_published' => 'bool',
        'display_order' => 'int',
        'created_by' => 'int',
        'updated_by' => 'int',
        'deleted_by' => 'int',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Automatically set created_by on creation
        static::creating(function (Faq $faq): void {
            if (Auth::check()) {
                $faq->created_by = Auth::id();
                $faq->updated_by = Auth::id();
            }
        });

        // Automatically set updated_by on update
        static::updating(function (Faq $faq): void {
            if (Auth::check()) {
                $faq->updated_by = Auth::id();
            }
        });

        // Automatically set deleted_by on soft delete
        static::deleting(function (Faq $faq): void {
            if (Auth::check() && !$faq->isForceDeleting()) {
                $faq->deleted_by = Auth::id();
                $faq->saveQuietly();
            }
        });
    }

    /**
     * Sanitize HTML content before saving.
     *
     * Removes dangerous tags and attributes while preserving
     * safe formatting (bold, italic, lists, links).
     *
     * @param string $value The raw HTML content
     * @return void
     */
    public function setAnswerAttribute(string $value): void
    {
        $this->attributes['answer'] = $this->sanitizeHtml($value);
    }

    /**
     * Sanitize HTML content.
     *
     * Security measures:
     * - Strips all script tags
     * - Removes javascript: protocol from links
     * - Removes on* event handlers
     * - Allows only safe HTML tags
     *
     * @param string $html The HTML to sanitize
     * @return string The sanitized HTML
     */
    private function sanitizeHtml(string $html): string
    {
        // Remove script tags and their content
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        
        // Remove javascript: protocol
        $html = preg_replace('/javascript:/i', '', $html);
        
        // Remove on* event handlers
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        
        // Strip tags, allowing only safe ones
        $allowedTags = '<p><br><strong><em><u><ul><ol><li><a>';
        $html = strip_tags($html, $allowedTags);
        
        // Additional link sanitization
        $html = preg_replace_callback(
            '/<a\s+([^>]*?)href\s*=\s*["\']([^"\']*)["\']([^>]*?)>/i',
            function ($matches) {
                $href = $matches[2];
                
                // Only allow http, https, and mailto protocols
                if (!preg_match('/^(https?:\/\/|mailto:)/i', $href)) {
                    return '<a>'; // Remove href if protocol is not safe
                }
                
                // Add rel="noopener noreferrer" for security
                return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" rel="noopener noreferrer" target="_blank">';
            },
            $html
        );
        
        return $html;
    }

    /**
     * Scope a query to only include published FAQs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope a query to order by display order.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc');
    }

    /**
     * Get the user who created this FAQ.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this FAQ.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this FAQ.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}

