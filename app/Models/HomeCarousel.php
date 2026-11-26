<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeCarousel extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'source_type', 'language', 'author_id', 'book_limit', 'is_active', 'sort_order',
        'system_key', 'show_unavailable',
        'link_enabled', 'link_type', 'link_category_id', 'link_path',
    ];

    protected $casts = [
        'book_limit'       => 'integer',
        'is_active'        => 'boolean',
        'sort_order'       => 'integer',
        'show_unavailable' => 'boolean',
        'link_enabled'     => 'boolean',
    ];

    /** Where the title's link may point: see HomeCarouselService::linkOf(). */
    public const LINK_TYPES = ['auto', 'category', 'path'];

    /**
     * Validation for the admin-controlled title link. Defined once here because
     * three rule sets need it: store, update, and update's built-in branch -
     * built-in carousels expose no source fields but DO expose these.
     */
    public static function linkRules(\Illuminate\Http\Request $request): array
    {
        // The target is only required when the link is actually switched ON and
        // that type is selected. required_if on link_type alone would block the
        // admin from turning a link OFF while the type still said "category"
        // with nothing picked yet.
        $enabled = $request->boolean('link_enabled');
        $type    = $request->input('link_type');

        return [
            'link_enabled'     => 'boolean',
            'link_type'        => 'required|in:' . implode(',', self::LINK_TYPES),
            'link_category_id' => [
                \Illuminate\Validation\Rule::requiredIf($enabled && $type === 'category'),
                'nullable',
                'integer',
                'exists:categories,id',
            ],
            'link_path'        => [
                \Illuminate\Validation\Rule::requiredIf($enabled && $type === 'path'),
                'nullable',
                'string',
                // 191, not 255: the schema's default string length is 191, and a
                // longer rule would accept input the column then truncates.
                'max:191',
                // One leading slash, and the next character may not be / or \ -
                // browsers read "//evil.com" and "/\evil.com" as protocol-relative
                // and would send a homepage title off-site.
                'regex:/^\/(?![\/\\\\])\S*$/',
            ],
        ];
    }

    /** Arabic messages for the link rules above. */
    public static function linkMessages(): array
    {
        return [
            'link_category_id.required'    => 'يجب اختيار الفئة التي يوجّه إليها العنوان',
            'link_path.required'           => 'يجب إدخال المسار الذي يوجّه إليه العنوان',
            'link_path.regex'              => 'يجب أن يبدأ المسار بشرطة مائلة واحدة (مثال: /offers) وأن يكون داخل الموقع',
            'link_path.max'                => 'المسار طويل جداً (الحد 191 حرفاً)',
        ];
    }

    /**
     * Registry of built-in carousels. Keyed by system_key; resolution logic lives
     * in HomeCarouselService. 'render' picks the homepage component; 'dom_id' keeps
     * the original wrapper id (some are linked from the header, e.g. #popular-books).
     * 'group' = global (cacheable) | personalized (per-user) | session.
     *
     * 'link' is optional and makes the carousel's title clickable: a route name
     * plus optional query/route params, resolved by HomeCarouselService::linkOf().
     * It is stored unresolved because a const cannot call route(). Most built-ins
     * have no 'link' because they have no listing page to send a visitor to —
     * there is no "all new arrivals", "all bestsellers" or "all books in one
     * language" page, and the personalized/session ones are not addressable at
     * all. A missing 'link' leaves the title as plain text.
     */
    public const SYSTEM = [
        'recommended'      => ['render' => 'books',      'dom_id' => 'recommended-for-you', 'group' => 'personalized'],
        'from_follows'     => ['render' => 'books',      'dom_id' => 'from-follows',        'group' => 'personalized'],
        'new_arrivals'     => ['render' => 'books',      'dom_id' => 'all-books',           'group' => 'global'],
        'categories_strip' => ['render' => 'categories', 'dom_id' => 'categories-strip',    'group' => 'global', 'link' => ['route' => 'categories.index']],
        'popular'          => ['render' => 'books',      'dom_id' => 'popular-books',       'group' => 'global'],
        'arabic_series'    => ['render' => 'series',     'dom_id' => 'arabic-series',       'group' => 'global', 'link' => ['route' => 'series.index', 'params' => ['language' => 'arabic']]],
        'accessories'      => ['render' => 'books',      'dom_id' => 'Accessories',         'group' => 'global', 'link' => ['route' => 'accessories.index']],
        'english_books'    => ['render' => 'books',      'dom_id' => 'english-books',       'group' => 'global'],
        'english_series'   => ['render' => 'series',     'dom_id' => 'english-series',      'group' => 'global', 'link' => ['route' => 'series.index', 'params' => ['language' => 'english']]],
        'french_books'     => ['render' => 'books',      'dom_id' => 'french-books',        'group' => 'global'],
        'recently_viewed'  => ['render' => 'books',      'dom_id' => 'recently-viewed',     'group' => 'session'],
    ];

    public function getIsSystemAttribute(): bool
    {
        return $this->system_key !== null;
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'home_carousel_category');
    }

    public function books()
    {
        return $this->belongsToMany(Book::class, 'home_carousel_book');
    }

    public function author()
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    /**
     * The category an admin chose as the title's destination. Distinct from
     * categories(), which is where the carousel's BOOKS come from - the two are
     * usually the same tree but do not have to be.
     */
    public function linkCategory()
    {
        return $this->belongsTo(Category::class, 'link_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Resolve the books shown in this carousel based on its source, in random
     * order, capped at book_limit. Returns standard (non-bundle) books only.
     */
    public function resolveBooks()
    {
        $query = Book::query()
            ->where('type', 'book')
            ->standardOnly()
            ->with(['primaryAuthor', 'authors', 'bundles:id,title,price,image'])
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg_rating', 'rating');

        // "Only available" toggle — restrict to in-stock books.
        if (!$this->show_unavailable) {
            $query->where('quantity', '>', 0);
        }

        // Optional language filter (author/categories sources can span languages).
        if ($this->language) {
            $query->where('language', $this->language);
        }

        switch ($this->source_type) {
            case 'author':
                if (!$this->author_id) {
                    return collect();
                }
                $authorId = $this->author_id;
                $query->where(function ($q) use ($authorId) {
                    $q->where('author_id', $authorId)
                      ->orWhereHas('authors', fn($a) => $a->where('authors.id', $authorId));
                });
                break;

            case 'manual':
                $ids = $this->books()->pluck('books.id')->all();
                if (empty($ids)) {
                    return collect();
                }
                $query->whereIn('books.id', $ids);
                break;

            case 'categories':
            default:
                $categoryIds = $this->categories()->pluck('categories.id')->all();
                if (empty($categoryIds)) {
                    return collect();
                }
                $query->whereHas('categories', fn($c) => $c->whereIn('categories.id', $categoryIds));
                break;
        }

        return $query->inRandomOrder()
            ->limit($this->book_limit ?: 12)
            ->get();
    }
}
