<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingBook extends Model
{
    use HasFactory;

    protected $table = 'pending_books';

    public const STATUS_ENRICHED  = 'enriched';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_DUPLICATE = 'duplicate';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_DISCARDED = 'discarded';

    /** Source-priority order — used for default field/image picks on review. */
    public const SOURCE_PRIORITY = ['bnf', 'google_books', 'open_library', 'wikipedia'];

    protected $fillable = [
        'title',
        'author_name',
        'author_id',
        'language',
        'status',
        'api_results',
        'staging_images',
        'error_message',
        'existing_book_id',
        'approved_book_id',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'api_results'    => 'array',
        'staging_images' => 'array',
        'reviewed_at'    => 'datetime',
    ];

    public function existingBook()
    {
        return $this->belongsTo(Book::class, 'existing_book_id');
    }

    public function author()
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    public function approvedBook()
    {
        return $this->belongsTo(Book::class, 'approved_book_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(UserModel::class, 'reviewed_by');
    }

    public function isReviewable(): bool
    {
        return in_array($this->status, [self::STATUS_ENRICHED, self::STATUS_FAILED], true);
    }

    /** Single source's normalized result, or null. */
    public function getResult(string $source): ?array
    {
        return $this->api_results[$source] ?? null;
    }

    public function hasImage(string $source): bool
    {
        return !empty(($this->staging_images ?? [])[$source] ?? null);
    }

    /**
     * Sources that returned any data, in canonical priority order.
     * @return array<int, string>
     */
    public function availableSources(): array
    {
        $available = array_keys($this->api_results ?? []);
        return array_values(array_intersect(self::SOURCE_PRIORITY, $available));
    }

    /** First source that returned a cover, in priority order. Null if none. */
    public function getDefaultImageSource(): ?string
    {
        foreach (self::SOURCE_PRIORITY as $src) {
            if ($this->hasImage($src)) {
                return $src;
            }
        }
        return null;
    }

    /**
     * For a given field, return the first source that returned a non-empty value.
     * Used by the review page to default-select a radio button per row.
     */
    public function getDefaultSourceForField(string $field): ?string
    {
        foreach (self::SOURCE_PRIORITY as $src) {
            $value = $this->api_results[$src][$field] ?? null;
            if (is_string($value) && trim($value) !== '') return $src;
            if (is_int($value)    && $value > 0)          return $src;
            if (is_array($value)  && !empty($value))      return $src;
        }
        return null;
    }
}
