@php $outOfStock = ($book->Quantity ?? 0) <= 0; @endphp
<div class="book-item list-style d-flex mb-3 p-3 border rounded {{ $outOfStock ? 'out-of-stock' : '' }}">
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
