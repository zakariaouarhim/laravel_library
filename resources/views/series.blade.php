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
                        <span><i class="fas fa-book"></i> {{ $series->books_count }} كتاب</span>
                        @if($series->total_volumes)
                            <span><i class="fas fa-list-ol"></i> {{ $series->total_volumes }} جزء</span>
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
                    </ul>
                </div>
            </div>
        </div>
    </div>

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
