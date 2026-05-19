@props(['series', 'title'])

@if($series && $series->count() > 0)
<div class="related-books series-carousel" data-carousel>
    <h3>{{ $title }}</h3>

    <div class="carousel-container">
        <div class="carousel-wrapper" data-carousel-wrapper>
            @foreach($series as $s)
                @php
                    $bundle = $s->bundle;
                    $bundleAvailable = $bundle && ($bundle->quantity ?? 0) > 0;
                @endphp
                <div class="book-card series-card">
                    <a class="book-image-wrapper" href="{{ route('series.show', $s) }}">
                        <img src="{{ $s->cover_image ? asset('storage/' . $s->cover_image) : asset('images/book-placeholder.png') }}"
                             alt="{{ $s->name }}" width="200" height="280" loading="lazy"
                             onerror="this.onerror=null;this.src='{{ asset('images/book-placeholder.png') }}'">
                    </a>

                    <div class="card-badges">
                        @if($s->is_complete)
                            <span class="badge bg-success">مكتملة</span>
                        @else
                            <span class="badge bg-warning text-dark">مستمرة</span>
                        @endif
                    </div>

                    <h6><a href="{{ route('series.show', $s) }}">{{ $s->name }}</a></h6>

                    @if($s->author)
                        <p class="book-author"><i class="fas fa-user-edit"></i> {{ $s->author->name }}</p>
                    @endif

                    <p class="series-meta">
                        <i class="fas fa-layer-group"></i>
                        {{ $s->total_volumes }} {{ $s->total_volumes == 1 ? 'جزء' : 'أجزاء' }}
                    </p>

                    @if($bundleAvailable)
                        <div class="price-section">
                            <span class="price">
                                {{ number_format((float) $bundle->price, 2) }} <span class="currency">د.م</span>
                                <small class="bundle-price-label">السلسلة كاملة</small>
                            </span>
                            <button class="add-btn" title="إضافة الباقة للسلة"
                                    onclick="addToCart({{ $bundle->id }},'{{ addslashes($bundle->title) }}', {{ $bundle->price }}, '{{ addslashes($bundle->image) }}')">
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                        </div>
                    @elseif($bundle)
                        <div class="price-section">
                            <span class="price">
                                {{ number_format((float) $bundle->price, 2) }} <span class="currency">د.م</span>
                                <small class="bundle-price-label">السلسلة كاملة</small>
                            </span>
                            <a class="add-btn" href="{{ route('moredetail2.page', $bundle) }}" title="عرض الباقة">
                                <i class="fas fa-box"></i>
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <button class="carousel-nav prev" data-carousel-prev>
            <i class="fas fa-chevron-right"></i>
        </button>
        <button class="carousel-nav next" data-carousel-next>
            <i class="fas fa-chevron-left"></i>
        </button>
        <br>
        <div class="carousel-indicators" data-carousel-indicators hidden="true"></div>
    </div>
</div>
@endif
