<div class="book-item list-style d-flex mb-3 p-3 border rounded">
    <img src="{{ asset($book->image ?? 'images/book-placeholder.png') }}" alt="{{ $book->title }}" style="width: 120px;" class="me-3" loading="lazy">
    <div>
        <h5><a href="{{ route('moredetail2.page', ['id' => $book->id]) }}">{{ $book->title }}</a></h5>
        <p><i class="fas fa-user-edit me-1"></i>{{ $book->author }}</p>
        <p class="text-muted">{{ Str::limit($book->description, 100) }}</p>
        <div class="d-flex align-items-center mt-2">
            <span class="fw-bold text-primary me-3">{{ $book->price }} د.م</span>
            @if($book->original_price ?? 0 > $book->price)
                <del class="text-muted">{{ $book->original_price }} د.م</del>
            @endif
            <button class="btn btn-sm btn-outline-primary ms-auto" onclick="addToCart({{ $book->id }},'{{ $book->title }}', {{ $book->price }}, '{{ $book->image }}')">
                <i class="fas fa-shopping-cart me-1"></i> أضف للسلة
            </button>
        </div>
    </div>
</div>
