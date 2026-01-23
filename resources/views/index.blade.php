<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مكتبة بيع الكتب</title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" integrity="sha384-gXt9imSW0VcJVHezoNQsP+TNrjYXoGcrqBZJpry9zJt8PCQjobwmhMGaDHTASo9N" crossorigin="anonymous">

    <!-- Correct CSS linking -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Index-searchbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/carouselstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/categories_carousel2.css') }}">
    
    
    
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">

    <!-- Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Scheherazade+New&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <meta name="csrf-token" content="{{ csrf_token() }}">

</head>

<body>
    
        @include('header')
        @include('Index-searchbar')
    
    
    <div class="layout-indexpage">
            <!-- First Carousel - Arabic Books -->
            <div class="related-books">
                <h3>كتب ذات صلة</h3>
                
                <div class="carousel-container">
                    <div class="carousel-wrapper" id="carouselWrapper1">
                        @foreach ($books as $index => $book)
                        <div class="book-card">
                            <a href="{{ route('moredetail.page', ['id' => $book->id]) }}">
                                <img src="{{ asset($book->image ?? 'images/books/default-book.png') }}" 
                                    class="card-img-top" 
                                    alt="{{ $book->title }}" 
                                    loading="lazy">
                            </a>
                            <h6>{{ $book->title }}</h6>
                            
                            <!-- Display author name from authors table -->
                            <p class="book-author">
                                <i class="fas fa-user-edit me-1"></i>
                                @if($book->primaryAuthor)
                                    {{ $book->primaryAuthor->name }}
                                    
                                @elseif($book->authors->where('pivot.author_type', 'primary')->first())
                                    {{ $book->authors->where('pivot.author_type', 'primary')->first()->name }}
                                @elseif($book->authors->isNotEmpty())
                                    {{ $book->authors->first()->name }}
                                    @if($book->authors->count() > 1)
                                        <small class="text-muted">+{{ $book->authors->count() - 1 }} مؤلف آخر</small>
                                    @endif
                                @else
                                    <span class="text-muted">مؤلف غير محدد</span>
                                @endif
                            </p>
                            
                            
                            
                            <div class="price-section">
                                <div class="text-center mb-3">
                                    <span class="h6 mb-0 text-gray text-through mr-2" style="text-decoration:line-through">
                                        {{ $book->price + 50 }}
                                    </span>
                                    <span class="h5 mb-0 text-danger">{{ $book->price }} درهم</span>
                                </div>
                                <button class="add-btn" onclick="addToCart({{ $book->id }},'{{ addslashes($book->title) }}', {{ $book->price }}, '{{ addslashes($book->image) }}')">
                                    <i class="fas fa-shopping-cart"> <span>أضف إلى السلة</span> </i>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <button class="carousel-nav prev" id="prevBtn1" onclick="moveCarousel(-1, 'carousel1')">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <button class="carousel-nav next" id="nextBtn1" onclick="moveCarousel(1, 'carousel1')">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <br>
                    <div class="carousel-indicators" id="indicators1" hidden="true"></div>
                </div>
            </div>
    
        <!-- carousel categories -->
        @include('categories_carousel2')
        
        <!-- Second Carousel - English Books -->
        <div class="related-books">
            <h3>ENGLISH BOOKS</h3>
            
            @if($EnglichBooks && $EnglichBooks->count() > 0)
                <div class="carousel-container">
                    <div class="carousel-wrapper" id="carouselWrapper2">
                        @foreach($EnglichBooks as $EnglichBook)
                        <div class="book-card">
                            <a href="{{ route('moredetail.page', ['id' => $EnglichBook->id]) }}">
                                <img src="{{ asset($EnglichBook->image) }}" class="card-img-top" alt="{{ $EnglichBook->title }}" loading="lazy">
                            </a>
                            <h6>{{ $EnglichBook->title }}</h6>
                            <p class="book-author">
                                <i class="fas fa-user-edit me-1"></i>
                                @if($EnglichBook->primaryAuthor)
                                    {{ $EnglichBook->primaryAuthor->name }}
                                    @if($EnglichBook->primaryAuthor->nationality)
                                        <small class="text-muted">({{ $EnglichBook->primaryAuthor->nationality }})</small>
                                    @endif
                                @elseif($EnglichBook->authors->where('pivot.author_type', 'primary')->first())
                                    {{ $EnglichBook->authors->where('pivot.author_type', 'primary')->first()->name }}
                                @elseif($EnglichBook->authors->isNotEmpty())
                                    {{ $EnglichBook->authors->first()->name }}
                                    @if($EnglichBook->authors->count() > 1)
                                        <small class="text-muted">+{{ $EnglichBook->authors->count() - 1 }} مؤلف آخر</small>
                                    @endif
                                @else
                                    <span class="text-muted">مؤلف غير محدد</span>
                                @endif
                            </p>
                            <div class="price-section">
                                <div class="text-center mb-3">
                                    <span class="h6 mb-0 text-gray text-through mr-2" style="text-decoration:line-through">
                                        {{ $EnglichBook->price + 50 }}
                                    </span>
                                    <span class="h5 mb-0 text-danger">{{ $EnglichBook->price }} درهم</span>
                                </div>
                                <button class="add-btn" onclick="addToCart({{ $EnglichBook->id }},'{{ addslashes($EnglichBook->title) }}', {{ $EnglichBook->price }}, '{{ addslashes($EnglichBook->image) }}')">
                                    <i class="fas fa-shopping-cart"></i>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <button class="carousel-nav prev" id="prevBtn2" onclick="moveCarousel(-1, 'carousel2')">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <button class="carousel-nav next" id="nextBtn2" onclick="moveCarousel(1, 'carousel2')">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <br>
                    <div class="carousel-indicators" id="indicators2"></div>
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
                        <div class="d-flex justify-content-center gap-3">
                            <a href="{{ route('index.page') }}" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>العودة للرئيسية
                            </a>
                            <a href="#" class="btn btn-outline-primary" onclick="window.history.back();">
                                <i class="fas fa-arrow-right me-2"></i>العودة للخلف
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <!-- third carousel Carousel - popular Books -->
        <div class="related-books">
                <h3> الأكثر مبيعا </h3>
                
                <div class="carousel-container">
                    <div class="carousel-wrapper" id="carouselWrapper3">
                        @foreach ($popularBooks as $index => $popularBook)
                        <div class="book-card">
                            <a href="{{ route('moredetail.page', ['id' => $popularBook->id]) }}">
                                <img src="{{ asset($popularBook->image ?? 'images/books/default-book.png') }}" 
                                    class="card-img-top" 
                                    alt="{{ $popularBook->title }}" 
                                    loading="lazy">
                            </a>
                            <h6>{{ $popularBook->title }}</h6>
                            
                            <!-- Display author name from authors table -->
                            <p class="book-author">
                                <i class="fas fa-user-edit me-1"></i>
                                @if($popularBook->primaryAuthor)
                                    {{ $popularBook->primaryAuthor->name }}
                                    
                                @elseif($popularBook->authors->where('pivot.author_type', 'primary')->first())
                                    {{ $popularBook->authors->where('pivot.author_type', 'primary')->first()->name }}
                                @elseif($popularBook->authors->isNotEmpty())
                                    {{ $popularBook->authors->first()->name }}
                                    @if($popularBook->authors->count() > 1)
                                        <small class="text-muted">+{{ $popularBook->authors->count() - 1 }} مؤلف آخر</small>
                                    @endif
                                @else
                                    <span class="text-muted">مؤلف غير محدد</span>
                                @endif
                            </p>
                            
                            
                            
                            <div class="price-section">
                                <div class="text-center mb-3">
                                    <span class="h6 mb-0 text-gray text-through mr-2" style="text-decoration:line-through">
                                        {{ $popularBook->price + 50 }}
                                    </span>
                                    <span class="h5 mb-0 text-danger">{{ $popularBook->price }} درهم</span>
                                </div>
                                <button class="add-btn" onclick="addToCart({{ $popularBook->id }},'{{ addslashes($popularBook->title) }}', {{ $popularBook->price }}, '{{ addslashes($book->image) }}')">
                                    <i class="fas fa-shopping-cart"> <span>أضف إلى السلة</span> </i>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <button class="carousel-nav prev" id="prevBtn3" onclick="moveCarousel(-1, 'carousel3')">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <button class="carousel-nav next" id="nextBtn3" onclick="moveCarousel(1, 'carousel3')">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <br>
                    <div class="carousel-indicators" id="indicators3" hidden="true"></div>
                </div>
            </div>
    </div>
     
    
    

        
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ asset('js/carousel.js') }}"></script>
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/Index-searchbar.js') }}"></script>
    
    <script src="{{ asset('js/categories_carousel2.js') }}"></script>
    <footer>
        @include('footer')
    </footer>
        
    
    



</body>
</html>


