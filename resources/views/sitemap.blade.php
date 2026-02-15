{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    {{-- Static Pages --}}
    <url>
        <loc>{{ url('/') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ route('about.page') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc>{{ route('contact.page') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc>{{ route('categories.index') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc>{{ route('authors.index') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc>{{ route('accessories.index') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>

    {{-- Books --}}
    @foreach($books as $book)
    <url>
        <loc>{{ route('moredetail.page', $book->id) }}</loc>
        @if($book->updated_at)
        <lastmod>{{ $book->updated_at->toW3cString() }}</lastmod>
        @endif
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach

    {{-- Accessories --}}
    @foreach($accessories as $accessory)
    <url>
        <loc>{{ route('moredetail.page', $accessory->id) }}</loc>
        @if($accessory->updated_at)
        <lastmod>{{ $accessory->updated_at->toW3cString() }}</lastmod>
        @endif
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
    @endforeach

    {{-- Authors --}}
    @foreach($authors as $author)
    <url>
        <loc>{{ route('author.show', $author->id) }}</loc>
        @if($author->updated_at)
        <lastmod>{{ $author->updated_at->toW3cString() }}</lastmod>
        @endif
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    @endforeach

    {{-- Categories --}}
    @foreach($categories as $category)
    <url>
        <loc>{{ route('by-category', $category->id) }}</loc>
        @if($category->updated_at)
        <lastmod>{{ $category->updated_at->toW3cString() }}</lastmod>
        @endif
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach
</urlset>
