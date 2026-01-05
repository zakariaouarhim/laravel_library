<!-- Category Carousel Section -->
    <section class="category-section2">
        <h2>أشعر الآن وكأنني أريد...</h2>
        
        <div class="carousel-container">
            <!-- Carousel Wrapper - Native scrollable -->
            <div class="carousel-wrapper" id="carouselWrapper">
                

                @foreach ($categorieIcons as $ci )
                    
                
                <a href="#" class="category-card">
                    <div class="category-icon">
                        <i class="{{ $ci->categorie_icon }}"></i>
                    </div>
                    <div class="category-title">{{ $ci->name }}</div>
                </a>
                @endforeach
               
            </div>
        </div>
    </section>