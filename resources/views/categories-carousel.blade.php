<section class="category-section" aria-label="Catégories de lecture">
        <h2>En ce moment, j'ai envie de...</h2>
        
        <div class="carousel-container">
            <div class="carousel-wrapper" id="carouselWrapper">
                
                @foreach($categorieImages as $category)
                    <a href="#" class="category-card">
                        
                            <img 
                                src="{{ asset($category->categorie_image) }}" 
                                alt="{{ $category->name }}" 
                                class="category-image"
                                loading="lazy">
                            
                        <div class="category-overlay"></div>
                        <div class="category-title">{{ $category->name }}</div>
                        
                    </a>
                @endforeach
            </div>
        </div>
    </section>