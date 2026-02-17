<div class="layout-searchbar">
    <header class="hero-search-section text-white text-center py-5">
        <!-- Background with falling Arabic letters -->
        <div class="letters-background" id="letters-container"></div>

        <!-- Content container with search -->
        <div class="container search-container">
            <h1 class="display-4 fw-bold">ابحث عن كتابك المفضل</h1>
            <p class="lead">ابحث في مجموعتنا الكبيرة من الكتب عبر الأنواع والتصنيفات.</p>

            <form action="{{ route('search.results') }}" method="GET" class="search-box">
                <input
                    type="text"
                    name="query"
                    id="searchInput"
                    placeholder="ابحث عن كتاب بالعنوان، المؤلف، أو النوع..."
                    oninput="searchBooksAutocomplete(this.value)"
                    autocomplete="off"
                    required>
                <button type="submit">
                    <i class="fas fa-search"></i> بحث
                </button>
                <!-- Search Results Container for Autocomplete -->
                <div id="searchResults" class="search-results"></div>
            </form>
        </div>

        <!-- Category buttons below the search bar -->
        <div class="categories-wrapper">
            <div class="category-rows">
                <div class="category-row">
                    @foreach ($categorie as $category)
                    <button class="category-btn small" onclick="window.location.href='{{ route('by-category', ['category' => $category->id]) }}'">{{ $category->name }}</button>
                    @endforeach
                    <button class="category-btn small" onclick="window.location.href='{{ route('categories.index') }}'">المزيد</button>
                </div>
            </div>
        </div>
    </header>
</div>
