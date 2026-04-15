@props(['series', 'title'])

@if($series && $series->count() > 0)
<div class="related-books series-carousel" data-carousel>
    <h3>{{ $title }}</h3>

    <div class="carousel-container">
        <div class="carousel-wrapper" data-carousel-wrapper>
            @foreach($series as $s)
                <a class="book-card series-card" href="{{ route('series.show', $s->id) }}">
                    <div class="book-image-wrapper">
                        <img src="{{ $s->cover_image ? asset('storage/' . $s->cover_image) : asset('images/book-placeholder.png') }}"
                             alt="{{ $s->name }}" width="200" height="280" loading="lazy"
                             onerror="this.onerror=null;this.src='{{ asset('images/book-placeholder.png') }}'">
                    </div>

                    <div class="card-badges">
                        @if($s->is_complete)
                            <span class="badge bg-success">مكتملة</span>
                        @else
                            <span class="badge bg-warning text-dark">مستمرة</span>
                        @endif
                    </div>

                    <h6>{{ $s->name }}</h6>

                    @if($s->author)
                        <p class="book-author"><i class="fas fa-user-edit"></i> {{ $s->author->name }}</p>
                    @endif

                    <p class="series-meta">
                        <i class="fas fa-layer-group"></i>
                        {{ $s->books_count }} {{ $s->books_count == 1 ? 'جزء' : 'أجزاء' }}
                    </p>
                </a>
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
