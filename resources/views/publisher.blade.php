<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $publisher->name }} - مكتبة الفقراء</title>
    @include('partials.meta-tags', [
        'metaTitle' => $publisher->name . ' - مكتبة الفقراء',
        'metaDescription' => Str::limit($publisher->description ?? 'اكتشف كتب ' . $publisher->name . ' المتوفرة في مكتبة الفقراء', 160),
        'metaImage' => $publisher->logo ? asset('storage/' . $publisher->logo) : asset('images/logo.svg'),
        'metaType' => 'profile',
        'metaUrl' => route('publisher.show', $publisher->id),
    ])

    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
    <link rel="stylesheet" href="{{ asset('css/publisher-profile.css') }}">
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
                    <li class="breadcrumb-item"><a href="{{ route('publishers.index') }}">دور النشر</a></li>
                    <li class="breadcrumb-item active">{{ $publisher->name }}</li>
                </ol>
            </nav>
            <div class="hero-profile">
                <div class="hero-logo">
                    @if($publisher->logo)
                        <img src="{{ asset('storage/' . $publisher->logo) }}" alt="{{ $publisher->name }}">
                    @else
                        <div class="logo-placeholder">
                            <i class="fas fa-building"></i>
                        </div>
                    @endif
                </div>
                <div class="hero-info">
                    <h1>{{ $publisher->name }}</h1>
                    <div class="hero-meta">
                        @if($publisher->country)
                            <span><i class="fas fa-map-marker-alt"></i> {{ $publisher->country }}</span>
                        @endif
                        @if($publisher->founded_year)
                            <span><i class="fas fa-calendar-alt"></i> تأسست {{ $publisher->founded_year }}</span>
                        @endif
                        <span><i class="fas fa-book"></i> {{ $publisher->books_count }} كتاب</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Publisher Details -->
    <div class="container">
        <div class="publisher-details-section">
            <div class="details-grid">
                @if($publisher->description)
                    <div class="detail-card bio-card">
                        <div class="detail-card-header">
                            <i class="fas fa-info-circle"></i>
                            <h3>نبذة عن دار النشر</h3>
                        </div>
                        <p class="bio-text">{{ $publisher->description }}</p>
                    </div>
                @endif

                <div class="detail-card info-card">
                    <div class="detail-card-header">
                        <i class="fas fa-address-card"></i>
                        <h3>معلومات</h3>
                    </div>
                    <ul class="info-list">
                        @if($publisher->country)
                            <li>
                                <span class="info-label"><i class="fas fa-flag"></i> الدولة</span>
                                <span class="info-value">{{ $publisher->country }}</span>
                            </li>
                        @endif
                        @if($publisher->founded_year)
                            <li>
                                <span class="info-label"><i class="fas fa-calendar"></i> سنة التأسيس</span>
                                <span class="info-value">{{ $publisher->founded_year }}</span>
                            </li>
                        @endif
                        @if($publisher->years_in_business)
                            <li>
                                <span class="info-label"><i class="fas fa-clock"></i> سنوات النشاط</span>
                                <span class="info-value">{{ $publisher->years_in_business }} سنة</span>
                            </li>
                        @endif
                        @if($publisher->website)
                            <li>
                                <span class="info-label"><i class="fas fa-globe"></i> الموقع</span>
                                <a href="{{ $publisher->website }}" target="_blank" rel="noopener noreferrer" class="info-value info-link">
                                    زيارة الموقع <i class="fas fa-external-link-alt"></i>
                                </a>
                            </li>
                        @endif
                        @if($publisher->email)
                            <li>
                                <span class="info-label"><i class="fas fa-envelope"></i> البريد</span>
                                <span class="info-value">{{ $publisher->email }}</span>
                            </li>
                        @endif
                        @if($publisher->phone)
                            <li>
                                <span class="info-label"><i class="fas fa-phone"></i> الهاتف</span>
                                <span class="info-value">{{ $publisher->phone }}</span>
                            </li>
                        @endif
                        @if($publisher->address)
                            <li>
                                <span class="info-label"><i class="fas fa-map-marker-alt"></i> العنوان</span>
                                <span class="info-value">{{ $publisher->address }}</span>
                            </li>
                        @endif
                        <li>
                            <span class="info-label"><i class="fas fa-book-open"></i> عدد الكتب</span>
                            <span class="info-value">{{ $publisher->books_count }}</span>
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
                        <i class="fas fa-book"></i>
                        كتب دار النشر
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
                    <p>لم نعثر على كتب لدار النشر هذه في المتجر حالياً.</p>
                    <a href="{{ route('index.page') }}" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>العودة للرئيسية
                    </a>
                </div>
            @endif
        </div>
    </section>

    @include('footer')

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/cart.js') }}"></script>
    <script src="{{ asset('js/wishlist.js') }}"></script>
</body>
</html>
