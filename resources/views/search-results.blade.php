<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتائج البحث</title>
    
     <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" integrity="sha384-gXt9imSW0VcJVHezoNQsP+TNrjYXoGcrqBZJpry9zJt8PCQjobwmhMGaDHTASo9N" crossorigin="anonymous">

    <!-- Correct CSS linking -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/searchresult.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">

    <!-- Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Scheherazade+New&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    
</head>
<body  style="min-height: 100vh; display: flex; flex-direction: column; margin: 0;">
    @include('header')
    <!-- Search Header -->
    <div class="Layout-searchresult">
        <div class="search-header">
            <div class="container">
                <div class="search-box-container">
                    <h1 class="text-white text-center mb-4" style="font-family: 'Amiri', serif; font-size: 32px;">ابحث عن كتابك المفضل</h1>
                    <form action="#" method="GET" class="search-box">
                        <input 
                            type="text" 
                            name="query" 
                            placeholder="ابحث عن كتاب بالعنوان، المؤلف، أو النوع..."
                            oninput="searchBooksAutocomplete(this.value)"
                            value="{{ $query }}">
                            
                        <button type="submit">
                            <i class="fas fa-search"></i> بحث
                        </button>
                        <!-- Search Results Container for Autocomplete -->
                        <div id="searchResults" class="search-results" style="z-index: 9999 !important;" >
                            <!-- Search results will be inserted here dynamically -->
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Results Container -->
        <div class="results-container">
            <!-- Search Info -->
            <div class="search-info">
                <div>
                    <h2>نتائج البحث عن: <span class="results-count">"{{ $query }}"</span></h2>
                    <p style="margin: 5px 0 0; color: #718096;">تم العثور على <strong>{{ $count_relatedBooks }} كتاب</strong></p>
                </div>
                <!-- FILTER FORM -->
                <form method="GET" action="{{ route('search.results') }}"
                    class="filter-buttons d-flex gap-2 flex-wrap">

                    <!-- keep search query -->
                    <input type="hidden" name="query" value="{{ request('query') }}">

                    <!-- CATEGORY DROPDOWN -->
                    <select name="category" class="filter-btn"
                            onchange="this.form.submit()">
                        <option value="">كل التصنيفات</option>

                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}"
                                {{ request('category') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>

                            @foreach ($category->children as $child)
                                <option value="{{ $child->id }}"
                                    {{ request('category') == $child->id ? 'selected' : '' }}>
                                    — {{ $child->name }}
                                </option>
                            @endforeach
                        @endforeach
                    </select>

                    <!-- FILTER BUTTONS -->
                    <button type="submit"  name="filter" value=""
                        class="filter-btn {{ !request('filter') ? 'active' : '' }}">
                        الكل
                    </button>

                    <button type="submit"  name="filter" value="author"
                        class="filter-btn {{ request('filter') == 'author' ? 'active' : '' }}">
                        المؤلف
                    </button>

                    <button type="submit" name="filter" value="price_high"
                        class="filter-btn {{ request('filter') == 'price_high' ? 'active' : '' }}">
                        السعر ↑
                    </button>

                    <button type="submit"  name="filter" value="price_low"
                        class="filter-btn {{ request('filter') == 'price_low' ? 'active' : '' }}">
                        السعر ↓
                    </button>

                </form>
                
            </div>

            <!-- Books Grid -->
            <div class="books-grid">
                <!-- Books from search results -->
                @foreach ($books as $book)
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
                            @if($book->primaryAuthor->nationality)
                                <small class="text-muted">({{ $book->primaryAuthor->nationality }})</small>
                            @endif
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
                    
                    <!-- Optional: Display publishing house -->
                    @if($book->publishingHouse)
                    <p class="book-publisher">
                        <i class="fas fa-building me-1"></i>
                        <small class="text-muted">{{ $book->publishingHouse->name }}</small>
                    </p>
                    @elseif($book->Publishing_House)
                    <p class="book-publisher">
                        <i class="fas fa-building me-1"></i>
                        <small class="text-muted">{{ $book->Publishing_House }}</small>
                    </p>
                    @endif
                    
                    <div class="price-section">
                        <div class="text-center mb-3">
                            <span class="h6 mb-0 text-gray text-through mr-2" style="text-decoration:line-through">
                                {{ $book->price + 50 }}
                            </span>
                            <span class="h5 mb-0 text-danger">{{ $book->price }} درهم</span>
                        </div>
                        <button class="add-btn" onclick="addToCart({{ $book->id }},'{{ addslashes($book->title) }}', {{ $book->price }}, '{{ addslashes($book->image) }}')">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>
                @endforeach
                
                <!-- Related books -->
                    
                @if ($relatedBooks)
                @foreach ($relatedBooks as $relatedBook)
                <div class="book-card">
                    <a href="{{ route('moredetail.page', ['id' => $relatedBook->id]) }}">
                        <img src="{{ asset($relatedBook->image ?? 'images/books/default-book.png') }}" 
                            class="card-img-top" 
                            alt="{{ $relatedBook->title }}" 
                            loading="lazy">
                    </a>
                    <h6>{{ $relatedBook->title }}</h6>
                    
                    <!-- Display author name from authors table -->
                    <p class="book-author">
                        <i class="fas fa-user-edit me-1"></i>
                        @if($relatedBook->primaryAuthor)
                            {{ $relatedBook->primaryAuthor->name }}
                            @if($relatedBook->primaryAuthor->nationality)
                                <small class="text-muted">({{ $relatedBook->primaryAuthor->nationality }})</small>
                            @endif
                        @elseif($relatedBook->authors->where('pivot.author_type', 'primary')->first())
                            {{ $relatedBook->authors->where('pivot.author_type', 'primary')->first()->name }}
                        @elseif($relatedBook->authors->isNotEmpty())
                            {{ $relatedBook->authors->first()->name }}
                            @if($relatedBook->authors->count() > 1)
                                <small class="text-muted">+{{ $relatedBook->authors->count() - 1 }} مؤلف آخر</small>
                            @endif
                        @else
                            <span class="text-muted">مؤلف غير محدد</span>
                        @endif
                    </p>
                    
                    <!-- Optional: Display publishing house -->
                    @if($relatedBook->publishingHouse)
                    <p class="book-publisher">
                        <i class="fas fa-building me-1"></i>
                        <small class="text-muted">{{ $relatedBook->publishingHouse->name }}</small>
                    </p>
                    @elseif($relatedBook->Publishing_House)
                    <p class="book-publisher">
                        <i class="fas fa-building me-1"></i>
                        <small class="text-muted">{{ $relatedBook->Publishing_House }}</small>
                    </p>
                    @endif
                    
                    <div class="price-section">
                        <div class="text-center mb-3">
                            <span class="h6 mb-0 text-gray text-through mr-2" style="text-decoration:line-through">
                                {{ $relatedBook->price + 50 }}
                            </span>
                            <span class="h5 mb-0 text-danger">{{ $relatedBook->price }} درهم</span>
                        </div>
                        <button class="add-btn" onclick="addToCart({{ $relatedBook->id }},'{{ addslashes($relatedBook->title) }}', {{ $relatedBook->price }}, '{{ addslashes($relatedBook->image) }}')">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>
                @endforeach @endif
        </div>
                
            

            <!-- Uncomment for No Results State -->
            @if (empty($books))
                
            
            <div class="no-results">
                <div class="no-results-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>لم نعثر على نتائج</h3>
                <p>عذراً، لم نتمكن من العثور على كتب تطابق بحثك. جرب كلمات مفتاحية أخرى أو تصفح اقتراحاتنا.</p>
                
                <div class="suggestions">
                    <h4>جرب البحث عن:</h4>
                    <div class="suggestion-tags">
                        <a href="#" class="suggestion-tag">روايات عربية</a>
                        <a href="#" class="suggestion-tag">شعر عربي</a>
                        <a href="#" class="suggestion-tag">كتب تاريخ</a>
                        <a href="#" class="suggestion-tag">فلسفة</a>
                        <a href="#" class="suggestion-tag">علم النفس</a>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
        
        <script>
            // Filter functionality
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Favorite button functionality
            document.querySelectorAll('.btn-favorite').forEach(btn => {
                btn.addEventListener('click', function() {
                    const icon = this.querySelector('i');
                    if (icon.classList.contains('far')) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        this.style.background = '#fee';
                        this.style.borderColor = '#fc8181';
                        this.style.color = '#e53e3e';
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        this.style.background = '#f7fafc';
                        this.style.borderColor = '#e2e8f0';
                        this.style.color = '#718096';
                    }
                });
            });
        </script>
        <script src="{{ asset('js/header.js') }}"></script>
        <script src="{{ asset('js/Index-searchbar.js') }}"></script>
        <script src="{{ asset('js/scripts.js') }}"></script>
        <script src="{{ asset('js/Index-searchbar.js') }}"></script>
   @include('footer') 
</body>
</html>