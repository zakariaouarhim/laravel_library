<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $series->name }} - مكتبة الفقراء</title>
    @include('partials.meta-tags', [
        'metaTitle' => $series->name . ' - مكتبة الفقراء',
        'metaDescription' => Str::limit($series->description ?? 'اكتشف كتب سلسلة ' . $series->name . ' المتوفرة في مكتبة الفقراء', 160),
        'metaImage' => $series->cover_image ? asset('storage/' . $series->cover_image) : asset('images/logo.svg'),
        'metaType' => 'website',
        'metaUrl' => route('series.show', $series->id),
    ])

    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
    <link rel="stylesheet" href="{{ asset('css/publisher-pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Tajawal -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="auth-user" content="true">
    @endauth
</head>
<body>
    @include('header')

    <!-- Hero Section -->
    <div class="publisher-hero">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('index.page') }}"><i class="fas fa-home"></i> الرئيسية</a></li>
                    <li class="breadcrumb-item active">{{ $series->name }}</li>
                </ol>
            </nav>
            <div class="hero-profile">
                <div class="hero-logo">
                    @if($series->cover_image)
                        <img src="{{ asset('storage/' . $series->cover_image) }}" alt="{{ $series->name }}" width="120" height="120" loading="lazy">
                    @else
                        <div class="logo-placeholder">
                            <i class="fas fa-layer-group"></i>
                        </div>
                    @endif
                </div>
                <div class="hero-info">
                    <h1>{{ $series->name }}</h1>
                    <div class="hero-meta">
                        @if($series->author)
                            <span><i class="fas fa-pen-fancy"></i> <a href="{{ route('author.show', $series->author->id) }}" style="color:inherit;text-decoration:none;">{{ $series->author->name }}</a></span>
                        @endif
                        <span><i class="fas fa-book"></i> {{ $series->total_volumes }} كتاب</span>
                        @if($series->total_volumes)
                            <span><i class="fas fa-list-ol"></i> {{ $series->total_volumes }} جزء</span>
                        @endif
                        @if($series->language_label)
                            <span><i class="fas fa-language"></i> {{ $series->language_label }}</span>
                        @endif
                        <span>
                            <i class="fas {{ $series->is_complete ? 'fa-check-circle' : 'fa-spinner' }}"></i>
                            {{ $series->is_complete ? 'مكتملة' : 'مستمرة' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Series Details -->
    <div class="container">
        <div class="publisher-details-section">
            <div class="details-grid">
                @if($series->description)
                    <div class="detail-card bio-card">
                        <div class="detail-card-header">
                            <i class="fas fa-info-circle"></i>
                            <h3>عن السلسلة</h3>
                        </div>
                        <p class="bio-text">{{ $series->description }}</p>
                    </div>
                @endif

                <div class="detail-card info-card">
                    <div class="detail-card-header">
                        <i class="fas fa-address-card"></i>
                        <h3>معلومات</h3>
                    </div>
                    <ul class="info-list">
                        @if($series->author)
                            <li>
                                <span class="info-label"><i class="fas fa-pen-fancy"></i> المؤلف</span>
                                <a href="{{ route('author.show', $series->author->id) }}" class="info-value info-link">{{ $series->author->name }}</a>
                            </li>
                        @endif
                        @if($series->total_volumes)
                            <li>
                                <span class="info-label"><i class="fas fa-list-ol"></i> عدد الأجزاء</span>
                                <span class="info-value">{{ $series->total_volumes }}</span>
                            </li>
                        @endif
                        <li>
                            <span class="info-label"><i class="fas fa-book-open"></i> الكتب المتوفرة</span>
                            <span class="info-value">{{ $series->books_count }}</span>
                        </li>
                        <li>
                            <span class="info-label"><i class="fas fa-info-circle"></i> الحالة</span>
                            <span class="info-value">{{ $series->is_complete ? 'مكتملة' : 'مستمرة' }}</span>
                        </li>
                        @if($series->language_label)
                            <li>
                                <span class="info-label"><i class="fas fa-language"></i> اللغة</span>
                                <span class="info-value">{{ $series->language_label }}</span>
                            </li>
                        @endif
                        @if($categories->count() > 0)
                            <li>
                                <span class="info-label"><i class="fas fa-tags"></i> التصنيفات</span>
                                <span class="info-value series-category-chips">
                                    @foreach($categories as $cat)
                                        <a href="{{ route('by-category', $cat->id) }}" class="series-cat-chip">{{ $cat->name }}</a>
                                    @endforeach
                                </span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <style>
        .series-category-chips { display: inline-flex; flex-wrap: wrap; gap: 6px; justify-content: flex-end; }
        .series-cat-chip {
            background: #eef2ff;
            color: #3730a3;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 0.8rem;
            text-decoration: none;
            transition: background 0.15s ease;
        }
        .series-cat-chip:hover { background: #c7d2fe; color: #3730a3; text-decoration: none; }
    </style>

    <!-- Bundles Section -->
    @if(isset($bundles) && $bundles->count() > 0)
    <section class="publisher-books">
        <div class="container">
            <div class="books-section">
                <h2 class="section-title">
                    <i class="fas fa-box"></i>
                    اشترِ السلسلة كاملة — باقات متوفرة
                </h2>
                <div class="series-bundles-grid">
                    @foreach($bundles as $bundle)
                        @php
                            $itemsTotal = $bundle->items->sum(fn($i) => ((float)$i->price) * ($i->pivot->quantity ?? 1));
                            $savings = $itemsTotal - (float) $bundle->price;
                        @endphp
                        <div class="bundle-card">
                            <a href="{{ route('moredetail2.page', $bundle->id) }}" class="bundle-cover">
                                @if($bundle->image)
                                    <img src="{{ asset($bundle->image) }}" alt="{{ $bundle->title }}" loading="lazy">
                                @else
                                    <div class="bundle-cover-placeholder"><i class="fas fa-box"></i></div>
                                @endif
                            </a>
                            <div class="bundle-info">
                                <h3 class="bundle-title">
                                    <a href="{{ route('moredetail2.page', $bundle->id) }}">{{ $bundle->title }}</a>
                                </h3>
                                <p class="bundle-meta">{{ $bundle->items->count() }} أجزاء مشمولة</p>
                                <div class="bundle-price-row">
                                    <span class="bundle-price">{{ number_format((float)$bundle->price, 2) }}</span>
                                    @if($savings > 0)
                                        <span class="bundle-old-price">{{ number_format($itemsTotal, 2) }}</span>
                                        <span class="bundle-savings">توفير {{ number_format($savings, 2) }}</span>
                                    @endif
                                </div>
                                <a href="{{ route('moredetail2.page', $bundle->id) }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-shopping-bag me-1"></i>عرض الباقة
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    <style>
        .series-bundles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
        .bundle-card { display: flex; gap: 12px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px; transition: box-shadow 0.2s; }
        .bundle-card:hover { box-shadow: 0 6px 18px rgba(0,0,0,0.08); }
        .bundle-cover { flex-shrink: 0; width: 90px; height: 130px; border-radius: 6px; overflow: hidden; background: #f3f4f6; display:flex; align-items:center; justify-content:center; }
        .bundle-cover img { width: 100%; height: 100%; object-fit: cover; }
        .bundle-cover-placeholder { font-size: 2rem; color: #9ca3af; }
        .bundle-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px; }
        .bundle-title { font-size: 1rem; font-weight: 700; margin: 0; line-height: 1.3; }
        .bundle-title a { color: inherit; text-decoration: none; }
        .bundle-title a:hover { color: var(--color-primary, #2563eb); }
        .bundle-meta { margin: 0; font-size: 0.85rem; color: #6b7280; }
        .bundle-price-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .bundle-price { font-size: 1.15rem; font-weight: 700; color: var(--color-primary, #2563eb); }
        .bundle-old-price { text-decoration: line-through; color: #9ca3af; font-size: 0.9rem; }
        .bundle-savings { background: #ecfdf5; color: #065f46; padding: 2px 8px; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
    </style>
    @endif

    <!-- Books Section -->
    <section class="publisher-books">
        <div class="container">
            @if($books->count() > 0)
                <div class="books-section">
                    <h2 class="section-title">
                        <i class="fas fa-layer-group"></i>
                        أجزاء السلسلة
                    </h2>
                    <div class="books-grid">
                        @foreach($books as $book)
                            @include('partials.book-card-grid', ['book' => $book])
                        @endforeach
                    </div>
                    @if($books->hasPages())
                        <div class="pagination-wrapper">
                            {{ $books->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-book-open"></i>
                    <h3>لا توجد كتب متوفرة حالياً</h3>
                    <p>لم نعثر على كتب لهذه السلسلة في المتجر حالياً.</p>
                    <a href="{{ route('index.page') }}" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>العودة للرئيسية
                    </a>
                </div>
            @endif
        </div>
    </section>

    @include('footer')

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="{{ asset('js/cart.js') }}" defer></script>
    <script src="{{ asset('js/scripts.js') }}" defer></script>
</body>
</html>
