<div class="related-books" data-carousel>
    <h3>{{ $title ?? 'كتب ' }}</h3>

    @if($books && $books->count() > 0)
        <div class="carousel-container">
            <div class="carousel-wrapper" data-carousel-wrapper>
                @foreach($books as $book)
                <div class="book-card">
                    <!-- Image wrapper with link - FULL WIDTH -->
                    <a href="{{ route('moredetail2.page', ['id' => $book->id]) }}" class="book-image-wrapper">
                        <img src="{{ asset($book->image) }}" alt="{{ $book->title }}" loading="lazy">
                    </a>

                    <!-- Quick Actions on top of image -->
                    <div class="quick-actions">
                        <button class="action-btn wishlist-btn" title="إضافة للمفضلة" onclick="toggleWishlist({{ $book->id }}, this)" data-book-id="{{ $book->id }}">
                            <i class="@if(in_array($book->id, $wishlistBookIds)) fas @else far @endif fa-heart"></i>
                        </button>
                        <button class="action-btn" title="إضافة للسلة" onclick="addToCart({{ $book->id }},'{{ addslashes($book->title) }}', {{ $book->price }}, '{{ addslashes($book->image) }}')">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>

                    <!-- Badges positioned over image -->
                    <div class="card-badges">
                        @if($book->is_new ?? false)
                            <span class="badge bg-success">جديد</span>
                        @endif
                        @if($book->discount ?? 0 > 0)
                            <span class="badge bg-danger">خصم {{ $book->discount }}%</span>
                        @endif
                    </div>

                    <!-- Card Content -->
                    <h6>{{ $book->title }}</h6>
                    
                    <p class="book-author">
                        <i class="fas fa-user-edit me-1"></i>
                        @if($book->primaryAuthor)
                            <a href="{{ route('author.show', $book->primaryAuthor->id) }}">{{ $book->primaryAuthor->name }}</a>
                            @if($book->primaryAuthor->nationality)
                                <small class="text-muted">({{ $book->primaryAuthor->nationality }})</small>
                            @endif
                        @elseif($book->authors->where('pivot.author_type', 'primary')->first())
                            @php $pivotAuthor = $book->authors->where('pivot.author_type', 'primary')->first(); @endphp
                            <a href="{{ route('author.show', $pivotAuthor->id) }}">{{ $pivotAuthor->name }}</a>
                        @elseif($book->authors->isNotEmpty())
                            <a href="{{ route('author.show', $book->authors->first()->id) }}">{{ $book->authors->first()->name }}</a>
                            @if($book->authors->count() > 1)
                                <small class="text-muted">+{{ $book->authors->count() - 1 }} مؤلف آخر</small>
                            @endif
                        @else
                            <span class="text-muted">مؤلف غير محدد</span>
                        @endif
                    </p>
                    
                    <div class="price-section">
                        <span class="price">{{ $book->price }} <span class="currency">ر.س</span></span>
                        @if(($book->discount ?? 0) > 0)
                            <span class="original-price">{{ round($book->price / (1 - $book->discount / 100)) }} <span class="currency">ر.س</span></span>
                        @endif
                        <button class="add-btn" onclick="addToCart({{ $book->id }},'{{ addslashes($book->title) }}', {{ $book->price }}, '{{ addslashes($book->image) }}')">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
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
    @else
        <!-- Empty state message -->
        <div class="empty-carousel-message">
            <div class="empty-state-card text-center p-5 border rounded bg-light">
                <div class="empty-state-icon mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle" 
                        style="width: 80px; height: 80px;">
                        <i class="fas fa-books text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
                <h4 class="text-dark mb-3">لا توجد كتب ذات صلة</h4>
                <p class="text-muted mb-4">
                    عذراً، لا توجد كتب أخرى متاحة في نفس فئة هذا الكتاب حالياً.<br>
                    يمكنك تصفح مجموعتنا الكاملة من الكتب للعثور على المزيد من الخيارات المثيرة.
                </p>
                @if($slot->isNotEmpty())
                    {{ $slot }}
                @else
                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ route('index.page') }}" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>العودة للرئيسية
                        </a>
                        <a href="#" class="btn btn-outline-primary" onclick="window.history.back();">
                            <i class="fas fa-arrow-right me-2"></i>العودة للخلف
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>