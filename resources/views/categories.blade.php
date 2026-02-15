<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأقسام - مكتبة الفقراء</title>
    @include('partials.meta-tags', [
        'metaTitle' => 'الأقسام - مكتبة الفقراء',
        'metaDescription' => 'تصفح جميع أقسام وتصنيفات الكتب المتوفرة في مكتبة الفقراء.',
    ])
    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/categories.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <!-- Google Fonts - Tajawal -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    @include('header')

    <!-- Hero -->
    <div class="categories-hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">الأقسام</h1>
                <p class="hero-subtitle">تصفح {{ $totalCategories }} قسم يضم {{ $totalBooks }} كتاب</p>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('index.page') }}"><i class="fas fa-home"></i> الرئيسية</a></li>
                        <li class="breadcrumb-item active">الأقسام</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <!-- Search Bar -->
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="categorySearch" placeholder="ابحث عن قسم..." autocomplete="off">
        </div>

        <!-- Categories Grid -->
        <div class="categories-grid" id="categoriesGrid">
            @if($categorie->count() > 0)
                @foreach ($categorie as $category)
                    <div class="category-card" data-name="{{ $category->name }}">
                        <a href="{{ route('by-category', ['category' => $category->id]) }}" class="category-link">
                            <div class="category-header">
                                <div class="category-icon">
                                    @if($category->categorie_icon)
                                        <i class="{{ $category->categorie_icon }}"></i>
                                    @else
                                        <i class="fas fa-book-open"></i>
                                    @endif
                                </div>
                                <div class="category-info">
                                    <h3>{{ $category->name }}</h3>
                                    <span class="books-count">{{ $category->total_books }} كتاب</span>
                                </div>
                            </div>
                        </a>

                        @if ($category->children->isNotEmpty())
                            <div class="category-toggle" onclick="toggleChildren(event, {{ $category->id }})">
                                <span>{{ $category->children->count() }} قسم فرعي</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>

                            <div id="children-{{ $category->id }}" class="children-container">
                                @foreach ($category->children as $child)
                                    <a href="{{ route('by-category', ['category' => $child->id]) }}" class="child-item">
                                        <i class="fas fa-angle-left"></i>
                                        <span>{{ $child->name }}</span>
                                        <span class="child-count">{{ $child->books_count }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>لا توجد أقسام</h3>
                    <p>لم يتم إضافة أي أقسام حتى الآن</p>
                </div>
            @endif
        </div>

        <!-- No Results -->
        <div class="no-results" id="noResults" style="display: none;">
            <i class="fas fa-search"></i>
            <h3>لا توجد نتائج</h3>
            <p>لم يتم العثور على أقسام تطابق بحثك</p>
        </div>
    </div>

    @include('footer')

    <!-- JS -->
    <script src="{{ asset('js/header.js') }}"></script>
    <script>
        function toggleChildren(event, id) {
            event.preventDefault();
            event.stopPropagation();
            const container = document.getElementById('children-' + id);
            const toggle = event.currentTarget;
            const icon = toggle.querySelector('i');

            container.classList.toggle('open');
            icon.classList.toggle('fa-chevron-down');
            icon.classList.toggle('fa-chevron-up');
        }

        const searchInput = document.getElementById('categorySearch');
        const grid = document.getElementById('categoriesGrid');
        const noResults = document.getElementById('noResults');

        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            const cards = grid.querySelectorAll('.category-card');
            let found = 0;

            cards.forEach(card => {
                const name = card.getAttribute('data-name').toLowerCase();
                const childrenText = card.textContent.toLowerCase();
                if (name.includes(query) || childrenText.includes(query)) {
                    card.style.display = '';
                    found++;
                } else {
                    card.style.display = 'none';
                }
            });

            noResults.style.display = found === 0 ? '' : 'none';
        });
    </script>
</body>
</html>
