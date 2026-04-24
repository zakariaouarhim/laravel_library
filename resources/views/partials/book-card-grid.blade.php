@php
    $outOfStock  = ($book->quantity ?? 0) <= 0;
    $inBundle    = $book->isStandard() && $book->relationLoaded('bundles') && $book->bundles->isNotEmpty();
    $bundledOnly = $inBundle && $outOfStock;
    $firstBundle = $inBundle ? $book->bundles->first() : null;
@endphp
<div class="book-item">
    <div class="book-card {{ ($outOfStock && !$bundledOnly) ? 'out-of-stock' : '' }}">
        <div class="quick-actions">
            <button class="action-btn wishlist-btn" title="إضافة للمفضلة" onclick="toggleWishlist({{ $book->id }}, this)" data-book-id="{{ $book->id }}">
                <i class="@if(in_array($book->id, $wishlistBookIds)) fas @else far @endif fa-heart"></i>
            </button>
            @if($bundledOnly)
                <a class="action-btn" href="{{ route('moredetail2.page', $book->id) }}" title="عرض الباقة">
                    <i class="fas fa-box"></i>
                </a>
            @elseif(!$outOfStock)
                <button class="action-btn" title="إضافة للسلة" onclick="addToCart({{ $book->id }},'{{ addslashes($book->title) }}', {{ $book->price }}, '{{ addslashes($book->image) }}')">
                    <i class="fas fa-shopping-cart"></i>
                </button>
            @endif
        </div>

        <a href="{{ route('moredetail2.page', ['id' => $book->id]) }}" class="book-image-wrapper">
            <img src="{{ asset($book->thumbnail) }}" alt="{{ $book->title }}" width="200" height="280" loading="lazy"
                 srcset="{{ asset($book->thumbnail) }} 150w, {{ asset($book->image ?? 'images/book-placeholder.png') }} 400w"
                 sizes="200px"
                 onerror="this.onerror=null;this.src='{{ asset('images/book-placeholder.png') }}'">
        </a>

        @include('partials._book-card-badges')

        <h6><a href="{{ route('moredetail2.page', ['id' => $book->id]) }}">{{ $book->title }}</a></h6>

        <p class="book-author">
            <i class="fas fa-user-edit me-1"></i>
            @if($book->primaryAuthor)
                <a href="{{ route('author.show', $book->primaryAuthor->id) }}">{{ $book->primaryAuthor->name }}</a>
                @if($book->primaryAuthor->nationality)
                    <small class="text-muted">({{ $book->primaryAuthor->nationality }})</small>
                @endif
            @elseif($book->relationLoaded('authors') && $book->authors->where('pivot.author_type', 'primary')->first())
                @php $pivotAuthor = $book->authors->where('pivot.author_type', 'primary')->first(); @endphp
                <a href="{{ route('author.show', $pivotAuthor->id) }}">{{ $pivotAuthor->name }}</a>
            @elseif($book->relationLoaded('authors') && $book->authors->isNotEmpty())
                <a href="{{ route('author.show', $book->authors->first()->id) }}">{{ $book->authors->first()->name }}</a>
                @if($book->authors->count() > 1)
                    <small class="text-muted">+{{ $book->authors->count() - 1 }} مؤلف آخر</small>
                @endif
            @elseif($book->author_name)
                {{ $book->author_name }}
            @else
                <span class="text-muted">مؤلف غير محدد</span>
            @endif
        </p>

        @if($book->relationLoaded('series') && $book->series)
            <p class="book-series"><i class="fas fa-layer-group"></i> {{ $book->series->name }}@if($book->volume_number) — الجزء {{ $book->volume_number }}@endif</p>
        @endif

        @include('partials._book-card-rating')

        <div class="price-section">
            @if($bundledOnly && $firstBundle)
                <span class="price">
                    {{ number_format((float) $firstBundle->price, 2) }} <span class="currency">د.م</span>
                    <small class="bundle-price-label">السلسلة كاملة</small>
                </span>
                <a class="add-btn" href="{{ route('moredetail2.page', $book->id) }}" title="عرض الباقة">
                    <i class="fas fa-box"></i>
                </a>
            @else
                <span class="price">{{ $book->price }} <span class="currency">د.م</span></span>
                @if(($book->discount ?? 0) > 0)
                    <span class="original-price">{{ round($book->price / (1 - $book->discount / 100)) }} <span class="currency">د.م</span></span>
                @endif
                @if($outOfStock)
                    <button class="notify-btn" onclick="notifyStock({{ $book->id }}, this)">
                        <i class="fas fa-bell"></i> أبلغني عند التوفر
                    </button>
                @else
                    <button class="add-btn" onclick="addToCart({{ $book->id }},'{{ addslashes($book->title) }}', {{ $book->price }}, '{{ addslashes($book->image) }}')">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                @endif
            @endif
        </div>
    </div>
</div>
