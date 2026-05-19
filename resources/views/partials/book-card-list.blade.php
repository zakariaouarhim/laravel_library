@php
    $outOfStock  = ($book->quantity ?? 0) <= 0;
    $inBundle    = $book->isStandard() && $book->relationLoaded('bundles') && $book->bundles->isNotEmpty();
    $bundledOnly = $inBundle && $outOfStock;
    $firstBundle = $inBundle ? $book->bundles->first() : null;
@endphp
<div class="book-item list-style d-flex mb-3 p-3 border rounded {{ ($outOfStock && !$bundledOnly) ? 'out-of-stock' : '' }}">
    <img src="{{ asset($book->thumbnail) }}" alt="{{ $book->title }}" width="120" height="170" class="me-3" loading="lazy"
         onerror="this.onerror=null;this.src='{{ asset('images/book-placeholder.png') }}'">
    <div>
        <h5><a href="{{ route('moredetail2.page', $book) }}">{{ $book->title }}</a></h5>
        <p><i class="fas fa-user-edit me-1"></i>{{ $book->author_name }}</p>
        @if($book->relationLoaded('series') && $book->series)
            <p class="book-series"><i class="fas fa-layer-group me-1"></i>{{ $book->series->name }}@if($book->volume_number) — الجزء {{ $book->volume_number }}@endif</p>
        @endif
        @if($bundledOnly)
            <span class="badge badge-bundle-only mb-2"><i class="fas fa-box"></i> متوفر كباقة</span>
        @elseif($inBundle && $firstBundle)
            <a href="{{ route('moredetail2.page', $firstBundle) }}" class="badge badge-bundle-hint mb-2">
                <i class="fas fa-box"></i> متوفر أيضاً كباقة
            </a>
        @endif
        @include('partials._book-card-rating')
        <p class="text-muted">{{ Str::limit($book->description, 100) }}</p>
        <div class="d-flex align-items-center mt-2">
            @if($bundledOnly && $firstBundle)
                <span class="fw-bold text-primary me-3">
                    {{ number_format((float) $firstBundle->price, 2) }} د.م
                    <small class="bundle-price-label d-block">السلسلة كاملة</small>
                </span>
                <a class="btn btn-sm btn-outline-success ms-auto" href="{{ route('moredetail2.page', $book) }}">
                    <i class="fas fa-box me-1"></i> عرض الباقة
                </a>
            @else
                <span class="fw-bold text-primary me-3">{{ $book->price }} د.م</span>
                @if(($book->discount ?? 0) > 0)
                    <del class="text-muted">{{ round($book->price / (1 - $book->discount / 100)) }} د.م</del>
                @endif
                @if($outOfStock)
                    <span class="badge out-of-stock-badge ms-2 me-2">نفذ المخزون</span>
                    <button class="btn btn-sm btn-notify-outline ms-auto" onclick="notifyStock({{ $book->id }}, this)">
                        <i class="fas fa-bell me-1"></i> أبلغني عند التوفر
                    </button>
                @else
                    <button class="btn btn-sm btn-outline-primary ms-auto" onclick="addToCart({{ $book->id }},'{{ addslashes($book->title) }}', {{ $book->price }}, '{{ addslashes($book->image) }}')">
                        <i class="fas fa-shopping-cart me-1"></i> أضف للسلة
                    </button>
                @endif
            @endif
        </div>
    </div>
</div>
