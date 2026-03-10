@php $outOfStock = ($book->quantity ?? 0) <= 0; @endphp
<div class="book-item list-style d-flex mb-3 p-3 border rounded {{ $outOfStock ? 'out-of-stock' : '' }}">
    <img src="{{ asset($book->thumbnail) }}" alt="{{ $book->title }}" width="120" height="170" class="me-3" loading="lazy"
         onerror="this.onerror=null;this.src='{{ asset('images/book-placeholder.png') }}'">
    <div>
        <h5><a href="{{ route('moredetail2.page', ['id' => $book->id]) }}">{{ $book->title }}</a></h5>
        <p><i class="fas fa-user-edit me-1"></i>{{ $book->author_name }}</p>
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
        <p class="text-muted">{{ Str::limit($book->description, 100) }}</p>
        <div class="d-flex align-items-center mt-2">
            <span class="fw-bold text-primary me-3">{{ $book->price }} د.م</span>
            @if($book->original_price ?? 0 > $book->price)
                <del class="text-muted">{{ $book->original_price }} د.م</del>
            @endif
            @if($outOfStock)
                <span class="badge out-of-stock-badge ms-2 me-2">نفذ المخزون</span>
                <button class="btn btn-sm btn-notify-outline ms-auto" onclick="notifyStock({{ $book->id }}, this)">
                    <i class="fas fa-bell me-1"></i> أبلغني عند التوفر
                </button>
            @else
                <button class="btn btn-sm btn-outline-primary ms-auto" onclick="addToCart({{ $book->id }},'{{ $book->title }}', {{ $book->price }}, '{{ $book->image }}')">
                    <i class="fas fa-shopping-cart me-1"></i> أضف للسلة
                </button>
            @endif
        </div>
    </div>
</div>
