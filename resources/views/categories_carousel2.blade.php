<!-- Category Carousel Section -->
    <section class="category-section2">
        {{-- The <a> wraps only the text: the decorative gradient bar is an
             ::after on the h2 itself, so wrapping the heading would move it. --}}
        <h2>
            @if($link ?? null)
                <a href="{{ $link }}">{{ $title ?? 'أشعر الآن وكأنني أريد...' }}</a>
            @else
                {{ $title ?? 'أشعر الآن وكأنني أريد...' }}
            @endif
        </h2>
        
        <div class="carousel-container">
            <!-- Carousel Wrapper - Native scrollable -->
            <div class="carousel-wrapper" id="carouselWrapper">
                @foreach ($categorieIcons as $ci )
                <a href="{{ route('by-category', $ci) }}" class="category-card">
                    <div class="category-icon">
                        <i class="{{ $ci->categorie_icon }}"></i>
                    </div>
                    <div class="category-title">{{ $ci->name }}</div>
                </a>
                @endforeach
               
            </div>
        </div>
    </section>