<div class="book-item">
    <div class="book-card">
        <div class="card-badges">
            @if($book->is_new ?? false)
                <span class="badge bg-success">جديد</span>
            @endif
            @if($book->discount ?? 0 > 0)
                <span class="badge bg-danger">خصم {{ $book->discount }}%</span>
            @endif
        </div>

        <div class="quick-actions">
            <button class="action-btn" title="إضافة للمفضلة"><i class="far fa-heart"></i></button>
            <button class="action-btn" title="إضافة للسلة" onclick="addToCart({{ $book->id }},'{{ $book->title }}', {{ $book->price }}, '{{ $book->image }}')">
                <i class="fas fa-shopping-cart"></i>
            </button>
        </div>

        <a href="{{ route('moredetail.page', ['id' => $book->id]) }}" class="book-image-wrapper">
            <img src="{{ asset($book->image ?? 'images/book-placeholder.png') }}" alt="{{ $book->title }}">
        </a>

        <div class="book-details">
            <h6><a href="{{ route('moredetail.page', ['id' => $book->id]) }}">{{ $book->title }}</a></h6>
            <p class="author"><i class="fas fa-user-edit"></i> {{ $book->author }}</p>
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
