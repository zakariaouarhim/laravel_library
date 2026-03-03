@php
    $approvedReviews = $book->reviews->where('status', 'approved');
    $ratingCount = $approvedReviews->count();
    $avgRating = $approvedReviews->avg('rating') ?? 0;
    $authorName = $book->primaryAuthor?->name ?? $book->author ?? null;
    $publisherName = $book->publishingHouse?->name ?? $book->Publishing_House ?? null;

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
            'availability' => ($book->Quantity ?? 0) > 0
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'url' => route('moredetail2.page', $book->id),
        ],
    ];

    if ($book->ISBN) $schema['isbn'] = $book->ISBN;
    if ($book->Langue) $schema['inLanguage'] = $book->Langue;
    if ($book->Page_Num) $schema['numberOfPages'] = (int) $book->Page_Num;
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
