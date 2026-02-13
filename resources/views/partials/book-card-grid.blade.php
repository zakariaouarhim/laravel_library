@php
    // Get wishlist items for current user or session
    if (auth()->check()) {
        $wishlistBookIds = auth()->user()->wishlist()->pluck('book_id')->toArray();
    } else {
        $wishlistBookIds = session()->get('wishlist', []);
    }
@endphp

<div class="book-item">
    <div class="book-card">
        <div class="quick-actions">
            <button class="action-btn wishlist-btn" title="إضافة للمفضلة" onclick="toggleWishlist({{ $book->id }}, this)" data-book-id="{{ $book->id }}">
                <i class="@if(in_array($book->id, $wishlistBookIds)) fas @else far @endif fa-heart"></i>
            </button>
            <button class="action-btn" title="إضافة للسلة" onclick="addToCart({{ $book->id }},'{{ $book->title }}', {{ $book->price }}, '{{ $book->image }}')">
                <i class="fas fa-shopping-cart"></i>
            </button>
        </div>

        <a href="{{ route('moredetail.page', ['id' => $book->id]) }}" class="book-image-wrapper">
            <img src="{{ asset($book->image ?? 'images/book-placeholder.png') }}" alt="{{ $book->title }}">
        </a>

        <div class="card-badges">
            @if($book->is_new ?? false)
                <span class="badge bg-success">جديد</span>
            @endif
            @if($book->discount ?? 0 > 0)
                <span class="badge bg-danger">خصم {{ $book->discount }}%</span>
            @endif
        </div>
        
        <div class="book-details">
            <h6><a href="{{ route('moredetail.page', ['id' => $book->id]) }}">{{ $book->title }}</a></h6>
            @if ($book->author)
                <p class="author"><i class="fas fa-user-edit"></i> {{ $book->author }}</p>
            @endif
            

            <div class="price-section">
                <span class="price">{{ $book->price }} <span class="currency">ر.س</span></span>
                @if($book->original_price ?? 0 > $book->price)
                    <span class="original-price">{{ $book->original_price }} <span class="currency">ر.س</span></span>
                @endif
                <button class="add-btn" onclick="addToCart({{ $book->id }},'{{ $book->title }}', {{ $book->price }}, '{{ $book->image }}')">
                    <i class="fas fa-shopping-cart"></i>
                </button>
            </div>
        </div>
    </div>
</div>