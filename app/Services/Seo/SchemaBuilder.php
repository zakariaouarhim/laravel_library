<?php

namespace App\Services\Seo;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\PublishingHouse;
use App\Models\Series;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds schema.org JSON-LD payloads (as plain associative arrays).
 *
 * Consumers pass the result to the <x-seo.json-ld> Blade component, which
 * json_encode()s + emits the <script type="application/ld+json"> tag.
 *
 * Null fields are stripped before emission via stripNulls() — Google ignores
 * them but the Rich Results Test flags empty fields as warnings.
 */
class SchemaBuilder
{
    public function forBook(Book $book): array
    {
        $approvedReviews = $book->relationLoaded('reviewsWithUsers')
            ? $book->reviewsWithUsers->where('status', 'approved')
            : collect();
        $ratingCount = $approvedReviews->count();
        $avgRating   = $approvedReviews->avg('rating') ?? 0;

        $authorName    = $book->primaryAuthor?->name ?? $book->author_name ?? null;
        $publisherName = $book->publishingHouse?->name ?? $book->publishing_house_name ?? null;

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => ['Book', 'Product'],
            'name'     => $book->title,
            'url'      => route('moredetail2.page', $book),
            'image'    => $book->image ? asset($book->image) : asset(config('seo.default_image')),
            'description' => $book->description ?: null,
            'isbn'        => $book->isbn ?: null,
            'inLanguage'  => $book->language ?: null,
            'numberOfPages' => $book->page_num ? (int) $book->page_num : null,
            'author'    => $authorName ? ['@type' => 'Person', 'name' => $authorName] : null,
            'publisher' => $publisherName ? ['@type' => 'Organization', 'name' => $publisherName] : null,
            'offers' => [
                '@type'         => 'Offer',
                'price'         => $book->price,
                'priceCurrency' => 'DZD',
                'availability'  => ($book->quantity ?? 0) > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'url'           => route('moredetail2.page', $book),
            ],
            'aggregateRating' => $ratingCount > 0 ? [
                '@type'       => 'AggregateRating',
                'ratingValue' => round($avgRating, 1),
                'reviewCount' => $ratingCount,
                'bestRating'  => 5,
                'worstRating' => 1,
            ] : null,
        ];

        return $this->stripNulls($schema);
    }

    public function forAuthor(Author $author): array
    {
        return $this->stripNulls([
            '@context'    => 'https://schema.org',
            '@type'       => 'Person',
            'name'        => $author->name,
            'url'         => route('author.show', $author),
            'image'       => $author->profile_image
                ? asset('storage/' . $author->profile_image)
                : null,
            'description' => $author->meta_description
                ?: ($author->biography ? Str::limit(strip_tags($author->biography), 300) : null),
            'nationality' => $author->nationality ?: null,
            'birthDate'   => $author->birth_date?->toDateString(),
            'deathDate'   => $author->death_date?->toDateString(),
            // Wikipedia sameAs only when api_source explicitly = 'wikipedia'.
            'sameAs'      => ($author->api_source === 'wikipedia' && $author->api_id)
                ? ["https://en.wikipedia.org/wiki/{$author->api_id}"]
                : null,
        ]);
    }

    public function forPublisher(PublishingHouse $publisher): array
    {
        $address = $publisher->address
            ? $this->stripNulls([
                '@type'          => 'PostalAddress',
                'streetAddress'  => $publisher->address,
                'addressCountry' => $publisher->country ?: null,
            ])
            : null;

        return $this->stripNulls([
            '@context'    => 'https://schema.org',
            '@type'       => 'Organization',
            'name'        => $publisher->name,
            'url'         => route('publisher.show', $publisher),
            'logo'        => $publisher->logo ? asset($publisher->logo) : null,
            'foundingDate' => $publisher->founded_year ? (string) $publisher->founded_year : null,
            'address'     => $address,
            'email'       => $publisher->email ?: null,
            'telephone'   => $publisher->phone ?: null,
            'description' => $publisher->meta_description
                ?: ($publisher->description ? Str::limit(strip_tags($publisher->description), 300) : null),
        ]);
    }

    /**
     * CollectionPage with embedded ItemList. $books may be paginator or collection.
     */
    public function forCategory(Category $category, $books): array
    {
        return $this->stripNulls([
            '@context'    => 'https://schema.org',
            '@type'       => 'CollectionPage',
            'name'        => $category->meta_title ?: "كتب {$category->name}",
            'description' => $category->meta_description ?: null,
            'url'         => route('by-category', $category),
            'mainEntity'  => $this->forItemList($books),
        ]);
    }

    /**
     * BookSeries with hasPart = ItemList of volumes.
     */
    public function forSeries(Series $series, $books): array
    {
        return $this->stripNulls([
            '@context'      => 'https://schema.org',
            '@type'         => 'BookSeries',
            'name'          => $series->name,
            'url'           => route('series.show', $series),
            'image'         => $series->cover_image ? asset('storage/' . $series->cover_image) : null,
            'author'        => $series->author
                ? ['@type' => 'Person', 'name' => $series->author->name]
                : null,
            'numberOfItems' => $series->total_volumes ?: null,
            'description'   => $series->description
                ? Str::limit(strip_tags($series->description), 300)
                : null,
            'hasPart'       => $books && $books->count() > 0 ? $this->forItemList($books) : null,
        ]);
    }

    /**
     * Plain ItemList of books — each entry links to the book detail page.
     * Accepts Collection or LengthAwarePaginator (the latter exposes ->items()).
     */
    public function forItemList($books, int $startPosition = 1): array
    {
        $items = $books instanceof Collection ? $books : collect($books->items() ?? $books);

        return [
            '@type'           => 'ItemList',
            'numberOfItems'   => $items->count(),
            'itemListElement' => $items->values()->map(fn ($book, $i) => [
                '@type'    => 'ListItem',
                'position' => $startPosition + $i,
                'url'      => route('moredetail2.page', $book),
                'name'     => $book->title,
            ])->all(),
        ];
    }

    /**
     * BreadcrumbList from a trail of ['label' => 'X', 'url' => '...'] items.
     * The last item typically omits url (it's the current page).
     */
    public function forBreadcrumbs(array $trail): array
    {
        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => collect($trail)->values()->map(fn ($crumb, $i) => $this->stripNulls([
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb['label'],
                'item'     => $crumb['url'] ?? null,
            ]))->all(),
        ];
    }

    /**
     * WebSite + SearchAction (sitelinks search box). Emitted on homepage only.
     */
    public function forWebsite(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type'    => 'WebSite',
            'name'     => config('seo.site_name'),
            'url'      => url('/'),
            'potentialAction' => [
                '@type'  => 'SearchAction',
                'target' => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => url('/search-results') . '?query={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * Sitewide Organization (emitted from layouts/public.blade.php on every page).
     */
    public function forOrganization(): array
    {
        return $this->stripNulls([
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => config('seo.organization.name'),
            'url'      => url('/'),
            'logo'     => asset(config('seo.organization.logo')),
            'sameAs'   => !empty(config('seo.organization.social'))
                ? array_values(config('seo.organization.social'))
                : null,
        ]);
    }

    /**
     * Recursively drop null values and empty arrays so the rendered JSON-LD
     * carries only meaningful fields. Required-by-schema fields should be
     * validated upstream by the caller; this is purely cosmetic cleanup.
     */
    private function stripNulls(array $schema): array
    {
        return collect($schema)
            ->map(fn ($v) => is_array($v) ? $this->stripNulls($v) : $v)
            ->reject(fn ($v) => $v === null || $v === '' || (is_array($v) && empty($v)))
            ->all();
    }
}
