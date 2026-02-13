<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $author->name }} - مكتبة الفقراء</title>
    <meta name="description" content="{{ Str::limit($author->biography, 160) }}">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
    <link rel="stylesheet" href="{{ asset('css/author-profile.css') }}">
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

    @php
        $totalBooks = $primaryBooks->total() + $primaryBooksViaFk->count() + $coAuthorBooks->count() + $translatedBooks->count() + $editedBooks->count() + $illustratedBooks->count();
    @endphp

    <!-- Hero Section -->
    <div class="author-hero">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('index.page') }}"><i class="fas fa-home"></i> الرئيسية</a></li>
                    <li class="breadcrumb-item active">{{ $author->name }}</li>
                </ol>
            </nav>
            <div class="hero-profile">
                <div class="hero-avatar">
                    @if($author->profile_image)
                        <img src="{{ asset('storage/' . $author->profile_image) }}" alt="{{ $author->name }}">
                    @else
                        <div class="avatar-placeholder">
                            <span>{{ mb_substr($author->name, 0, 1) }}</span>
                        </div>
                    @endif
                </div>
                <div class="hero-info">
                    <h1>{{ $author->name }}</h1>
                    <div class="hero-meta">
                        @if($author->nationality)
                            <span><i class="fas fa-globe-africa"></i> {{ $author->nationality }}</span>
                        @endif
                        @if($author->birth_date)
                            <span>
                                <i class="fas fa-calendar-alt"></i>
                                {{ $author->birth_date->format('Y') }}
                                @if($author->death_date)
                                    - {{ $author->death_date->format('Y') }}
                                @else
                                    - الآن
                                @endif
                            </span>
                        @endif
                        <span><i class="fas fa-book"></i> {{ $totalBooks }} كتاب</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Author Details -->
    <div class="container">
        <div class="author-details-section">
            {{-- Bio & Info Row --}}
            <div class="details-grid">
                {{-- Biography --}}
                @if($author->biography)
                    <div class="detail-card bio-card">
                        <div class="detail-card-header">
                            <i class="fas fa-feather-alt"></i>
                            <h3>نبذة عن المؤلف</h3>
                        </div>
                        <p class="bio-text">{{ $author->biography }}</p>
                    </div>
                @endif

                {{-- Quick Info --}}
                <div class="detail-card info-card">
                    <div class="detail-card-header">
                        <i class="fas fa-info-circle"></i>
                        <h3>معلومات</h3>
                    </div>
                    <ul class="info-list">
                        @if($author->nationality)
                            <li>
                                <span class="info-label"><i class="fas fa-flag"></i> الجنسية</span>
                                <span class="info-value">{{ $author->nationality }}</span>
                            </li>
                        @endif
                        @if($author->birth_date)
                            <li>
                                <span class="info-label"><i class="fas fa-birthday-cake"></i> تاريخ الميلاد</span>
                                <span class="info-value">{{ $author->birth_date->format('Y/m/d') }}</span>
                            </li>
                        @endif
                        @if($author->death_date)
                            <li>
                                <span class="info-label"><i class="fas fa-dove"></i> تاريخ الوفاة</span>
                                <span class="info-value">{{ $author->death_date->format('Y/m/d') }}</span>
                            </li>
                        @endif
                        @if($author->website)
                            <li>
                                <span class="info-label"><i class="fas fa-globe"></i> الموقع</span>
                                <a href="{{ $author->website }}" target="_blank" rel="noopener noreferrer" class="info-value info-link">
                                    زيارة الموقع <i class="fas fa-external-link-alt"></i>
                                </a>
                            </li>
                        @endif
                        <li>
                            <span class="info-label"><i class="fas fa-book-open"></i> عدد الكتب</span>
                            <span class="info-value">{{ $totalBooks }}</span>
                        </li>
                    </ul>

                    {{-- Role Stats --}}
                    <div class="role-stats">
                        @if($primaryBooks->total() + $primaryBooksViaFk->count() > 0)
                            <span class="role-badge role-primary">
                                <i class="fas fa-pen-fancy"></i> تأليف: {{ $primaryBooks->total() + $primaryBooksViaFk->count() }}
                            </span>
                        @endif
                        @if($coAuthorBooks->count() > 0)
                            <span class="role-badge role-coauthor">
                                <i class="fas fa-users"></i> مشاركة: {{ $coAuthorBooks->count() }}
                            </span>
                        @endif
                        @if($translatedBooks->count() > 0)
                            <span class="role-badge role-translator">
                                <i class="fas fa-language"></i> ترجمة: {{ $translatedBooks->count() }}
                            </span>
                        @endif
                        @if($editedBooks->count() > 0)
                            <span class="role-badge role-editor">
                                <i class="fas fa-edit"></i> تحرير: {{ $editedBooks->count() }}
                            </span>
                        @endif
                        @if($illustratedBooks->count() > 0)
                            <span class="role-badge role-illustrator">
                                <i class="fas fa-paint-brush"></i> رسم: {{ $illustratedBooks->count() }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Books Sections -->
    <section class="author-books">
        <div class="container">

            {{-- Primary Author Books --}}
            @if($primaryBooks->count() > 0 || $primaryBooksViaFk->count() > 0)
                <div class="books-section">
                    <h2 class="section-title">
                        <i class="fas fa-pen-fancy"></i>
                        كتب المؤلف
                    </h2>
                    <div class="books-grid">
                        @foreach($primaryBooks as $book)
                            @include('partials.book-card-grid', ['book' => $book])
                        @endforeach
                        @foreach($primaryBooksViaFk as $book)
                            @include('partials.book-card-grid', ['book' => $book])
                        @endforeach
                    </div>
                    @if($primaryBooks->hasPages())
                        <div class="pagination-wrapper">
                            {{ $primaryBooks->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            @endif

            {{-- Co-authored Books --}}
            @if($coAuthorBooks->count() > 0)
                <div class="books-section">
                    <h2 class="section-title">
                        <i class="fas fa-users"></i>
                        كتب شارك في تأليفها
                    </h2>
                    <div class="books-grid">
                        @foreach($coAuthorBooks as $book)
                            @include('partials.book-card-grid', ['book' => $book])
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Translated Books --}}
            @if($translatedBooks->count() > 0)
                <div class="books-section">
                    <h2 class="section-title">
                        <i class="fas fa-language"></i>
                        كتب ترجمها
                    </h2>
                    <div class="books-grid">
                        @foreach($translatedBooks as $book)
                            @include('partials.book-card-grid', ['book' => $book])
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Edited Books --}}
            @if($editedBooks->count() > 0)
                <div class="books-section">
                    <h2 class="section-title">
                        <i class="fas fa-edit"></i>
                        كتب حررها
                    </h2>
                    <div class="books-grid">
                        @foreach($editedBooks as $book)
                            @include('partials.book-card-grid', ['book' => $book])
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Illustrated Books --}}
            @if($illustratedBooks->count() > 0)
                <div class="books-section">
                    <h2 class="section-title">
                        <i class="fas fa-paint-brush"></i>
                        كتب رسمها
                    </h2>
                    <div class="books-grid">
                        @foreach($illustratedBooks as $book)
                            @include('partials.book-card-grid', ['book' => $book])
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Empty State --}}
            @if($totalBooks === 0)
                <div class="empty-state">
                    <i class="fas fa-book-open"></i>
                    <h3>لا توجد كتب متوفرة حالياً</h3>
                    <p>لم نعثر على كتب لهذا المؤلف في المتجر حالياً.</p>
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
