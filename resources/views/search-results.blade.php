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
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
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
                        <option value=""style="color:black; font-weight: 700;">كل التصنيفات</option>

                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}"
                                {{ request('category') == $category->id ? 'selected' : '' }} style="color:black; font-weight: 400;">
                                *{{ $category->name }}
                            </option>

                            @foreach ($category->children as $child)
                                <option value="{{ $child->id }}"
                                    {{ request('category') == $child->id ? 'selected' : '' }} style="color:black">
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
                    @include('partials.book-card-grid', ['book' => $book])
                @endforeach
                
                <!-- Related books -->
                    
                @if ($relatedBooks)
                @foreach ($relatedBooks as $book)
                    @include('partials.book-card-grid', ['book' => $book])
                @endforeach
                @endif
        </div>
                
            

            <!-- Uncomment for No Results State -->
            @if ($count_relatedBooks==0)
                
            
            <div class="no-results">
                <div class="no-results-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>لم نعثر على نتائج</h3>
                <p>
                   عذراً، لم نتمكن من العثور على كتب تطابق بحثك. جرب كلمات مفتاحية أخرى أو
                    <a href="{{ route('index.page') }}">تصفح اقتراحاتنا</a>
                </p>
                
                @if ($relatedCategories->isNotEmpty())
                <div class="suggestions">
                    <h4>
                        {{ request('category') ? 'تصنيفات ذات صلة:' : 'تصنيفات شائعة:' }}
                    </h4>
                    <div class="suggestion-tags">
                        @foreach ($relatedCategories as $category)
                            <a href="{{ route('by-category', ['category' => $category->id]) }}"
                            class="suggestion-tag">
                            {{ $category->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
                
                @endif

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
        <script src="{{ asset('js/card.js') }}"></script>
   @include('footer') 
</body>
</html>