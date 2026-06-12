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
        $brandName     = $publisherName ?: config('seo.organization.name');
        $currency      = 'MAD';

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
            'brand'     => ['@type' => 'Brand', 'name' => $brandName],
            'offers' => [
                '@type'                  => 'Offer',
                'price'                  => $book->price,
                'priceCurrency'          => $currency,
                'availability'           => ($book->quantity ?? 0) > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'url'                    => route('moredetail2.page', $book),
                'shippingDetails'        => $this->buildShippingDetails($currency),
                'hasMerchantReturnPolicy' => $this->buildReturnPolicy(),
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
     * FAQPage with N Question/Answer pairs. Pass a Collection of Faq models.
     * Returns [] when collection is empty (caller gates emission).
     */
    public function forFaqPage($faqs): array
    {
        $items = $faqs instanceof Collection ? $faqs : collect($faqs);
        if ($items->isEmpty()) {
            return [];
        }

        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $items->values()->map(fn ($f) => [
                '@type'          => 'Question',
                'name'           => $f->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => strip_tags($f->answer),
                ],
            ])->all(),
        ];
    }

    /**
     * Per-review Review objects for a book. Caller wraps the result in a single
     * @graph script so Google reads N reviews from one <script> tag.
     *
     * Returns [] when no approved reviews exist (caller gates emission).
     * Reuses the same approved-reviews filter as forBook().
     */
    public function forReviews(Book $book, int $limit = 10): array
    {
        if (!$book->relationLoaded('reviewsWithUsers')) {
            return [];
        }

        $approved = $book->reviewsWithUsers
            ->where('status', 'approved')
            ->sortByDesc('created_at')
            ->take($limit);

        if ($approved->isEmpty()) {
            return [];
        }

        $bookRef = ['@type' => 'Book', 'name' => $book->title, 'url' => route('moredetail2.page', $book)];

        return $approved->values()->map(fn ($r) => $this->stripNulls([
            '@type'         => 'Review',
            'reviewRating'  => [
                '@type'       => 'Rating',
                'ratingValue' => (int) $r->rating,
                'bestRating'  => 5,
                'worstRating' => 1,
            ],
            'author'        => ['@type' => 'Person', 'name' => $r->user?->name ?: 'قارئ'],
            'reviewBody'    => $r->comment ? Str::limit(strip_tags($r->comment), 500) : null,
            'datePublished' => $r->created_at?->toIso8601String(),
            'itemReviewed'  => $bookRef,
        ]))->all();
    }

    /**
     * Plain ItemList of books — each entry links to the book detail page.
     * Accepts Collection or LengthAwarePaginator (the latter exposes ->items()).
     *
     * When $name is provided, the result includes @context + name and is suitable
     * for standalone emission as its own <script type="application/ld+json"> block.
     */
    public function forItemList($books, int $startPosition = 1, ?string $name = null): array
    {
        $items = $books instanceof Collection ? $books : collect($books->items() ?? $books);

        $schema = [];
        if ($name !== null) {
            $schema['@context'] = 'https://schema.org';
        }
        $schema['@type']           = 'ItemList';
        if ($name !== null) {
            $schema['name'] = $name;
        }
        $schema['numberOfItems']   = $items->count();
        $schema['itemListElement'] = $items->values()->map(fn ($book, $i) => [
            '@type'    => 'ListItem',
            'position' => $startPosition + $i,
            'url'      => route('moredetail2.page', $book),
            'name'     => $book->title,
        ])->all();

        return $schema;
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
     * BookStore (specialized LocalBusiness) — emitted on homepage + contact page
     * when admin has populated a complete address in SystemSetting. Returns []
     * if addressLocality (city) is missing — empty/partial address worse than no
     * schema (Google demotes for incomplete LocalBusiness data).
     */
    public function forBookStore(): array
    {
        $get = fn ($k, $d = null) => \App\Models\SystemSetting::getSetting($k, $d);

        $city = trim((string) $get('store_city', ''));
        if ($city === '') {
            return [];
        }

        $lat = $get('store_latitude');
        $lng = $get('store_longitude');

        return $this->stripNulls([
            '@context'    => 'https://schema.org',
            '@type'       => 'BookStore',
            'name'        => config('seo.organization.name'),
            'url'         => url('/'),
            'image'       => asset(config('seo.organization.logo')),
            'telephone'   => $get('store_phone') ?: null,
            'email'       => $get('store_email') ?: null,
            'address'     => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $get('store_street') ?: null,
                'addressLocality' => $city,
                'addressRegion'   => $get('store_region') ?: null,
                'postalCode'      => $get('store_postal_code') ?: null,
                'addressCountry'  => $get('store_country', 'MA') ?: 'MA',
            ],
            'geo'         => ($lat !== '' && $lng !== '' && $lat !== null && $lng !== null) ? [
                '@type'     => 'GeoCoordinates',
                'latitude'  => (string) $lat,
                'longitude' => (string) $lng,
            ] : null,
            'openingHours' => $get('opening_hours') ?: null,
            'sameAs'       => !empty(config('seo.organization.social'))
                ? array_values(config('seo.organization.social'))
                : null,
        ]);
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
     * OfferShippingDetails read from SystemSetting. Rate reuses shipping_cost;
     * country reuses store_country (defaults MA). Caller passes currency so
     * shipping matches offer currency.
     */
    private function buildShippingDetails(string $currency): array
    {
        $get = fn ($k, $d = null) => \App\Models\SystemSetting::getSetting($k, $d);

        return [
            '@type'        => 'OfferShippingDetails',
            'shippingRate' => [
                '@type'    => 'MonetaryAmount',
                'value'    => (string) $get('shipping_cost', '25'),
                'currency' => $currency,
            ],
            'shippingDestination' => [
                '@type'          => 'DefinedRegion',
                'addressCountry' => $get('store_country', 'MA') ?: 'MA',
            ],
            'deliveryTime' => [
                '@type'        => 'ShippingDeliveryTime',
                'handlingTime' => [
                    '@type'    => 'QuantitativeValue',
                    'minValue' => (int) $get('shipping_handling_days_min', 0),
                    'maxValue' => (int) $get('shipping_handling_days_max', 1),
                    'unitCode' => 'DAY',
                ],
                'transitTime' => [
                    '@type'    => 'QuantitativeValue',
                    'minValue' => (int) $get('shipping_transit_days_min', 2),
                    'maxValue' => (int) $get('shipping_transit_days_max', 5),
                    'unitCode' => 'DAY',
                ],
            ],
        ];
    }

    /**
     * MerchantReturnPolicy from SystemSetting. Defaults: 7-day window, return
     * by mail, customer pays return shipping (matches typical Moroccan retail).
     * returnMethod / returnFees are short identifiers stored in settings and
     * expanded to full schema.org URLs here.
     */
    private function buildReturnPolicy(): array
    {
        $get = fn ($k, $d = null) => \App\Models\SystemSetting::getSetting($k, $d);

        return [
            '@type'                => 'MerchantReturnPolicy',
            'applicableCountry'    => $get('store_country', 'MA') ?: 'MA',
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
            'merchantReturnDays'   => (int) $get('return_window_days', 7),
            'returnMethod'         => 'https://schema.org/' . $get('return_method', 'ReturnByMail'),
            'returnFees'           => 'https://schema.org/' . $get('return_fees', 'ReturnFeesCustomerResponsibility'),
        ];
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
