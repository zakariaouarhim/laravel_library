@php $outOfStock = ($book->Quantity ?? 0) <= 0; @endphp
<div class="book-item">
    <div class="book-card {{ $outOfStock ? 'out-of-stock' : '' }}">
        <div class="quick-actions">
            <button class="action-btn wishlist-btn" title="إضافة للمفضلة" onclick="toggleWishlist({{ $book->id }}, this)" data-book-id="{{ $book->id }}">
                <i class="@if(in_array($book->id, $wishlistBookIds)) fas @else far @endif fa-heart"></i>
            </button>
            @if(!$outOfStock)
            <button class="action-btn" title="إضافة للسلة" onclick="addToCart({{ $book->id }},'{{ $book->title }}', {{ $book->price }}, '{{ $book->image }}')">
                <i class="fas fa-shopping-cart"></i>
            </button>
            @endif
        </div>

        <a href="{{ route('moredetail2.page', ['id' => $book->id]) }}" class="book-image-wrapper">
            <img src="{{ asset($book->image ?? 'images/book-placeholder.png') }}" alt="{{ $book->title }}" width="200" height="280" loading="lazy"
                 onerror="this.onerror=null;this.src='{{ asset('images/book-placeholder.png') }}'">
        </a>

        <div class="card-badges">
            @if($outOfStock)
                <span class="badge out-of-stock-badge">نفذ المخزون</span>
            @else
                @if($book->is_new ?? false)
                    <span class="badge bg-success">جديد</span>
                @endif
                @if($book->discount ?? 0 > 0)
                    <span class="badge bg-danger">خصم {{ $book->discount }}%</span>
                @endif
            @endif
            @if(($book->author_id && in_array($book->author_id, $followedAuthorIds ?? [])) || ($book->publishing_house_id && in_array($book->publishing_house_id, $followedPublisherIds ?? [])))
                <span class="badge badge-followed"><i class="fas fa-user-check"></i> متابَع</span>
            @endif
        </div>

        <h6><a href="{{ route('moredetail2.page', ['id' => $book->id]) }}">{{ $book->title }}</a></h6>
        @if ($book->primaryAuthor)
            <p class="book-author">
                <i class="fas fa-user-edit"></i>
                <a href="{{ route('author.show', $book->primaryAuthor->id) }}">{{ $book->primaryAuthor->name }}</a>
            </p>
        @elseif($book->author)
            <p class="book-author"><i class="fas fa-user-edit"></i> {{ $book->author }}</p>
        @endif

        @if(($book->reviews_count ?? 0) > 0)
        <div class="book-card-rating">
            @php $avgRating = round($book->reviews_avg_rating ?? 0, 1); @endphp
            @for($i = 1; $i <= 5; $i++)
                @if($i <= floor($avgRating))
                    <i class="fas fa-star"></i>
                @elseif($i - $avgRating < 1 && $i - $avgRating > 0)
                    <i class="fas fa-star-half-alt"></i>
                @else
                    <i class="far fa-star"></i>
                @endif
            @endfor
            <span class="rating-count">({{ $book->reviews_count }})</span>
        </div>
        @endif

        <div class="price-section">
            <span class="price">{{ $book->price }} <span class="currency">د.م</span></span>
            @if($book->original_price ?? 0 > $book->price)
                <span class="original-price">{{ $book->original_price }} <span class="currency">د.م</span></span>
            @endif
            @if($outOfStock)
                <button class="notify-btn" onclick="notifyStock({{ $book->id }}, this)">
                    <i class="fas fa-bell"></i> أبلغني عند التوفر
                </button>
            @else
                <button class="add-btn" onclick="addToCart({{ $book->id }},'{{ $book->title }}', {{ $book->price }}, '{{ $book->image }}')">
                    <i class="fas fa-shopping-cart"></i>
                </button>
            @endif
        </div>
    </div>
</div>
