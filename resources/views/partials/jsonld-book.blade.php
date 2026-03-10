@php
    $approvedReviews = $book->reviews->where('status', 'approved');
    $ratingCount = $approvedReviews->count();
    $avgRating = $approvedReviews->avg('rating') ?? 0;
    $authorName = $book->primaryAuthor?->name ?? $book->author_name ?? null;
    $publisherName = $book->publishingHouse?->name ?? $book->publishing_house_name ?? null;

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => ['Book', 'Product'],
        'name' => $book->title,
        'url' => route('moredetail2.page', $book->id),
        'image' => $book->image ? asset($book->image) : asset('images/logo.svg'),
        'description' => $book->description ?? null,
        'offers' => [
            '@type' => 'Offer',
            'price' => $book->price,
            'priceCurrency' => 'DZD',
            'availability' => ($book->quantity ?? 0) > 0
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'url' => route('moredetail2.page', $book->id),
        ],
    ];

    if ($book->isbn) $schema['isbn'] = $book->isbn;
    if ($book->language) $schema['inLanguage'] = $book->language;
    if ($book->page_num) $schema['numberOfPages'] = (int) $book->page_num;
    if ($authorName) $schema['author'] = ['@type' => 'Person', 'name' => $authorName];
    if ($publisherName) $schema['publisher'] = ['@type' => 'Organization', 'name' => $publisherName];

    if ($ratingCount > 0) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => round($avgRating, 1),
            'reviewCount' => $ratingCount,
            'bestRating' => 5,
            'worstRating' => 1,
        ];
    }
@endphp
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
