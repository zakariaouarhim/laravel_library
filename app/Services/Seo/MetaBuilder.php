<?php

namespace App\Services\Seo;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\PublishingHouse;
use App\Models\Series;
use Illuminate\Support\Str;

/**
 * Builds the per-page SEO payload (title, description, canonical, OG image, type, robots)
 * consumed by `<x-seo.head>` in the public layout. Always prefers admin-edited
 * meta_title / meta_description columns over generated fallbacks.
 */
class MetaBuilder
{
    public function forHomepage(): array
    {
        return [
            'title'       => config('seo.default_title'),
            'description' => config('seo.default_description'),
            // Canonical to root, not /index — same controller serves both, prefer the shorter URL.
            'canonical'   => url('/'),
            'image'       => $this->defaultImage(),
            'type'        => 'website',
        ];
    }

    public function forBook(Book $book): array
    {
        $authorName = $book->primaryAuthor?->name;
        $titleBase  = $authorName ? "{$book->title} — {$authorName}" : $book->title;

        $description = $book->meta_description
            ?: $this->clip(strip_tags($book->description ?? '') ?: $this->bookFallbackDescription($book, $authorName));

        return [
            'title'       => $book->meta_title ?: $this->clipTitle($titleBase),
            'description' => $description,
            'canonical'   => route('moredetail2.page', $book),
            'image'       => $book->image ? asset($book->image) : $this->defaultImage(),
            'type'        => 'product',
        ];
    }

    public function forAuthor(Author $author): array
    {
        $title = $author->meta_title ?: $this->clipTitle("كتب {$author->name} - مكتبة الفقراء");
        $bio   = strip_tags($author->biography ?? '');
        $description = $author->meta_description
            ?: $this->clip($bio !== '' ? $bio : "تصفح جميع كتب {$author->name} المتوفرة في مكتبة الفقراء.");

        return [
            'title'       => $title,
            'description' => $description,
            'canonical'   => route('author.show', $author),
            'image'       => $author->profile_image
                ? asset('storage/' . $author->profile_image)
                : $this->defaultImage(),
            'type'        => 'profile',
        ];
    }

    public function forCategory(Category $category, ?int $bookCount = null): array
    {
        $title = $category->meta_title ?: $this->clipTitle("كتب {$category->name} - مكتبة الفقراء");
        $countText = $bookCount !== null ? " ({$bookCount} كتاب)" : '';
        $description = $category->meta_description
            ?: $this->clip("تصفح أفضل كتب {$category->name} المتوفرة في مكتبة الفقراء{$countText} بأسعار مناسبة وشحن سريع.");

        return [
            'title'       => $title,
            'description' => $description,
            'canonical'   => route('by-category', $category),
            'image'       => $this->defaultImage(),
            'type'        => 'website',
        ];
    }

    public function forPublisher(PublishingHouse $publisher): array
    {
        $title = $publisher->meta_title ?: $this->clipTitle("إصدارات {$publisher->name} - مكتبة الفقراء");
        $description = $publisher->meta_description
            ?: $this->clip(strip_tags($publisher->description ?? '') ?: "تصفح إصدارات دار {$publisher->name} المتوفرة في مكتبة الفقراء.");

        return [
            'title'       => $title,
            'description' => $description,
            'canonical'   => route('publisher.show', $publisher),
            'image'       => $publisher->logo ? asset($publisher->logo) : $this->defaultImage(),
            'type'        => 'website',
        ];
    }

    public function forSeries(Series $series): array
    {
        $title       = $this->clipTitle("سلسلة {$series->name} - مكتبة الفقراء");
        $description = $this->clip(strip_tags($series->description ?? '') ?: "تصفح كتب سلسلة {$series->name} المتوفرة في مكتبة الفقراء.");

        return [
            'title'       => $title,
            'description' => $description,
            'canonical'   => route('series.show', $series),
            'image'       => $this->defaultImage(),
            'type'        => 'website',
        ];
    }

    /**
     * Search results — always noindex,follow.
     */
    public function forSearch(?string $query = null): array
    {
        $title = $query
            ? $this->clipTitle("نتائج البحث عن \"{$query}\" - مكتبة الفقراء")
            : 'البحث - مكتبة الفقراء';

        return [
            'title'       => $title,
            'description' => 'ابحث عن كتابك المفضل في مكتبة الفقراء. تصفح وفلتر النتائج حسب التصنيف والسعر واللغة.',
            'canonical'   => route('search.results'),
            'image'       => $this->defaultImage(),
            'type'        => 'website',
            'robots'      => 'noindex,follow',
        ];
    }

    /**
     * Static pages (about, contact, privacy, terms).
     */
    public function forStatic(string $title, string $description, ?string $canonical = null): array
    {
        return [
            'title'       => $this->clipTitle($title),
            'description' => $this->clip($description),
            'canonical'   => $canonical ?? url()->current(),
            'image'       => $this->defaultImage(),
            'type'        => 'website',
        ];
    }

    /**
     * Pages that must never appear in the index (cart, account, login, etc.).
     */
    public function forNoIndex(string $title, string $description = ''): array
    {
        return [
            'title'       => $this->clipTitle($title),
            'description' => $description !== '' ? $this->clip($description) : config('seo.default_description'),
            'canonical'   => url()->current(),
            'image'       => $this->defaultImage(),
            'type'        => 'website',
            'robots'      => 'noindex,follow',
        ];
    }

    /**
     * Paginated list pages (page > 1): canonical points to page 1 + noindex,follow.
     * Pass the page-1 URL via $canonical (without ?page= query).
     */
    public function withPaginationGuard(array $seo, int $currentPage, string $page1Url): array
    {
        if ($currentPage > 1) {
            $seo['robots']    = 'noindex,follow';
            $seo['canonical'] = $page1Url;
        }
        return $seo;
    }

    private function defaultImage(): string
    {
        return asset(config('seo.default_image'));
    }

    private function clip(string $text): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', $text)), config('seo.meta_description_max'), '');
    }

    private function clipTitle(string $text): string
    {
        return Str::limit(trim($text), config('seo.meta_title_max'), '');
    }

    private function bookFallbackDescription(Book $book, ?string $authorName): string
    {
        return $authorName
            ? "اطلب «{$book->title}» للكاتب {$authorName} الآن من مكتبة الفقراء بأسعار مناسبة وشحن سريع."
            : "اطلب «{$book->title}» الآن من مكتبة الفقراء بأسعار مناسبة وشحن سريع.";
    }
}
