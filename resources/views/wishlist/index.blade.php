<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة الأمنيات - مكتبة الفقراء</title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="auth-user" content="true">
    @endauth
</head>
<body>
    @include('header')

    <!-- Hero Banner -->
    <div class="wl-hero">
        <div class="container">
            <div class="wl-hero-content">
                <div class="wl-hero-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h1>قائمة الأمنيات</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}"><i class="fas fa-home"></i> الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">قائمة الأمنيات</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container wl-container">
        @if($wishlist->count() > 0)
            <!-- Summary Bar -->
            <div class="wl-summary">
                <div class="wl-summary-info">
                    <span class="wl-count">
                        <i class="fas fa-book-open"></i>
                        <strong>{{ $wishlist->count() }}</strong> كتاب في قائمتك
                    </span>
                    <span class="wl-total">
                        <i class="fas fa-tag"></i>
                        الإجمالي: <strong>{{ number_format($wishlist->sum('price'), 2) }} د.م</strong>
                    </span>
                </div>
                <div class="wl-actions">
                    <button class="wl-btn wl-btn-cart" onclick="addAllToCart()">
                        <i class="fas fa-cart-plus"></i> أضف الكل للسلة
                    </button>
                    <button class="wl-btn wl-btn-clear" onclick="confirmClearAll()">
                        <i class="fas fa-trash-alt"></i> مسح الكل
                    </button>
                </div>
            </div>

            <!-- Books Grid -->
            <div class="wl-grid" id="wishlistGrid">
                @foreach ($wishlist as $book)
                <div class="wl-card" id="wl-item-{{ $book->id }}">
                    <a href="{{ route('moredetail.page', ['id' => $book->id]) }}" class="wl-card-image">
                        <img src="{{ asset($book->image ?? 'images/book-placeholder.png') }}" alt="{{ $book->title }}" loading="lazy">
                        @if($book->discount ?? 0 > 0)
                        <span class="wl-badge-discount">خصم {{ $book->discount }}%</span>
                        @endif
                    </a>
                    <div class="wl-card-body">
                        <h6 class="wl-card-title">
                            <a href="{{ route('moredetail.page', ['id' => $book->id]) }}">{{ $book->title }}</a>
                        </h6>
                        @if ($book->primaryAuthor)
                        <p class="wl-card-author">
                            <i class="fas fa-user-edit"></i>
                            <a href="{{ route('author.show', $book->primaryAuthor->id) }}">{{ $book->primaryAuthor->name }}</a>
                        </p>
                        @elseif($book->author)
                        <p class="wl-card-author"><i class="fas fa-user-edit"></i> {{ $book->author }}</p>
                        @endif
                        @if($book->category)
                        <p class="wl-card-category">
                            <i class="fas fa-folder"></i> {{ $book->category->name }}
                        </p>
                        @endif
                        <div class="wl-card-price">
                            {{ $book->price }} <span>د.م</span>
                            @if($book->original_price ?? 0 > $book->price)
                            <span class="wl-original-price">{{ $book->original_price }} د.م</span>
                            @endif
                        </div>
                        <div class="wl-card-actions">
                            <button class="wl-btn-add-cart" onclick="addToCart({{ $book->id }}, {{ json_encode($book->title) }}, {{ $book->price }}, {{ json_encode($book->image) }})">
                                <i class="fas fa-shopping-cart"></i> أضف للسلة
                            </button>
                            <button class="wl-btn-remove" onclick="removeFromWishlist({{ $book->id }})">
                                <i class="fas fa-heart-broken"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="wl-empty">
                <div class="wl-empty-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3>قائمة الأمنيات فارغة</h3>
                <p>لم تقم بإضافة أي كتاب إلى قائمة أمنياتك بعد</p>
                @guest
                <p class="wl-empty-hint">
                    <i class="fas fa-info-circle"></i>
                    <a href="{{ route('login2.page') }}">سجّل دخولك</a> لحفظ قائمتك بشكل دائم
                </p>
                @endguest
                <a href="{{ url('/') }}" class="wl-btn wl-btn-cart">
                    <i class="fas fa-book-open"></i> تصفح الكتب
                </a>
            </div>
        @endif
    </div>

    <!-- Clear All Confirmation Modal -->
    <div class="modal fade" id="clearAllModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;">
                <div class="modal-body text-center p-4">
                    <div style="width:60px;height:60px;margin:0 auto 16px;background:linear-gradient(135deg,rgba(220,53,69,0.1),rgba(220,53,69,0.05));border-radius:50%;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-exclamation-triangle" style="font-size:1.5rem;color:#dc3545;"></i>
                    </div>
                    <h6 style="font-weight:700;margin-bottom:8px;">مسح قائمة الأمنيات</h6>
                    <p class="text-muted" style="font-size:0.9rem;margin-bottom:20px;">هل تريد إزالة جميع الكتب من قائمة أمنياتك؟</p>
                    <div class="d-flex gap-2">
                        <button class="btn flex-fill" style="background:linear-gradient(135deg,#dc3545,#c82333);color:#fff;border:none;border-radius:10px;font-weight:600;padding:10px;" onclick="clearAllWishlist()">
                            <i class="fas fa-trash-alt"></i> نعم، امسح الكل
                        </button>
                        <button class="btn flex-fill" style="border:2px solid #e9ecef;border-radius:10px;font-weight:600;padding:10px;" data-bs-dismiss="modal">إلغاء</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999;">
        <div id="wlToast" class="toast align-items-center border-0" role="alert" style="background:linear-gradient(135deg,#2C4B79,#48CAE4);color:#fff;border-radius:12px;">
            <div class="d-flex">
                <div class="toast-body fw-bold" id="wlToastMsg"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    @include('footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ asset('js/card.js') }}"></script>

    <script>
    const isAuth = {{ auth()->check() ? 'true' : 'false' }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function showWlToast(msg) {
        document.getElementById('wlToastMsg').textContent = msg;
        new bootstrap.Toast(document.getElementById('wlToast')).show();
    }

    function removeFromWishlist(bookId) {
        const url = isAuth
            ? `/wishlist/remove/${bookId}`
            : `/wishlist-session/remove/${bookId}`;

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const item = document.getElementById(`wl-item-${bookId}`);
                if (item) {
                    item.style.transition = 'opacity 0.3s, transform 0.3s';
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        item.remove();
                        // Check if grid is now empty
                        if (document.querySelectorAll('.wl-card').length === 0) {
                            location.reload();
                        }
                    }, 300);
                }
                // Update header badge
                const badge = document.querySelector('.wishlist-badge');
                if (badge) {
                    const count = parseInt(badge.textContent) - 1;
                    badge.textContent = count;
                    if (count <= 0) badge.style.display = 'none';
                }
                showWlToast('تمت إزالة الكتاب من قائمة الأمنيات');
            }
        })
        .catch(() => showWlToast('حدث خطأ أثناء الإزالة'));
    }

    function addAllToCart() {
        const cards = document.querySelectorAll('.wl-card');
        cards.forEach(card => {
            const btn = card.querySelector('.wl-btn-add-cart');
            if (btn) btn.click();
        });
        showWlToast('تمت إضافة جميع الكتب إلى السلة');
    }

    function confirmClearAll() {
        new bootstrap.Modal(document.getElementById('clearAllModal')).show();
    }

    function clearAllWishlist() {
        const cards = document.querySelectorAll('.wl-card');
        const promises = [];

        cards.forEach(card => {
            const bookId = card.id.replace('wl-item-', '');
            const url = isAuth
                ? `/wishlist/remove/${bookId}`
                : `/wishlist-session/remove/${bookId}`;

            promises.push(
                fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                })
            );
        });

        Promise.all(promises).then(() => {
            bootstrap.Modal.getInstance(document.getElementById('clearAllModal')).hide();
            location.reload();
        });
    }
    </script>
</body>
</html>

<style>
/* ===== Hero ===== */
.wl-hero {
    background: linear-gradient(135deg, #2C4B79 0%, #48CAE4 100%);
    color: #fff;
    padding: 2.5rem 0;
    text-align: center;
}

.wl-hero-content h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.wl-hero-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 12px;
    background: rgba(255,255,255,0.15);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.wl-hero .breadcrumb {
    font-size: 0.9rem;
}

.wl-hero .breadcrumb-item a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
}

.wl-hero .breadcrumb-item a:hover {
    color: #fff;
}

.wl-hero .breadcrumb-item.active {
    color: rgba(255,255,255,0.6);
}

.wl-hero .breadcrumb-item + .breadcrumb-item::before {
    color: rgba(255,255,255,0.4);
}

/* ===== Container ===== */
.wl-container {
    padding: 2rem 1rem;
    max-width: 1200px;
}

/* ===== Summary Bar ===== */
.wl-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    background: #fff;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.wl-summary-info {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.wl-count, .wl-total {
    font-size: 0.95rem;
    color: #555;
}

.wl-count i, .wl-total i {
    color: #2C4B79;
    margin-left: 4px;
}

.wl-actions {
    display: flex;
    gap: 8px;
}

.wl-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}

.wl-btn-cart {
    background: linear-gradient(135deg, #2C4B79, #48CAE4);
    color: #fff;
    box-shadow: 0 4px 12px rgba(44,75,121,0.2);
}

.wl-btn-cart:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(44,75,121,0.3);
    color: #fff;
}

.wl-btn-clear {
    background: #fff;
    color: #dc3545;
    border: 2px solid #fde8e8;
}

.wl-btn-clear:hover {
    background: #fde8e8;
}

/* ===== Books Grid ===== */
.wl-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.2rem;
}

.wl-card {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    display: flex;
    flex-direction: row;
    transition: all 0.3s;
}

.wl-card:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.wl-card-image {
    flex-shrink: 0;
    width: 120px;
    position: relative;
    display: block;
}

.wl-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    min-height: 170px;
}

.wl-badge-discount {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #dc3545;
    color: #fff;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
}

.wl-card-body {
    padding: 14px;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.wl-card-title {
    font-size: 0.95rem;
    font-weight: 700;
    margin-bottom: 6px;
    line-height: 1.4;
}

.wl-card-title a {
    color: #2c3e50;
    text-decoration: none;
}

.wl-card-title a:hover {
    color: #2C4B79;
}

.wl-card-author, .wl-card-category {
    font-size: 0.8rem;
    color: #777;
    margin-bottom: 4px;
}

.wl-card-author a {
    color: #777;
    text-decoration: none;
}

.wl-card-author a:hover {
    color: #2C4B79;
}

.wl-card-author i, .wl-card-category i {
    width: 16px;
    margin-left: 4px;
    color: #aaa;
}

.wl-card-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: #2C4B79;
    margin-top: auto;
    margin-bottom: 10px;
}

.wl-card-price span {
    font-size: 0.8rem;
    font-weight: 400;
}

.wl-original-price {
    font-size: 0.8rem !important;
    color: #aaa !important;
    text-decoration: line-through;
    margin-right: 6px;
    font-weight: 400 !important;
}

.wl-card-actions {
    display: flex;
    gap: 8px;
}

.wl-btn-add-cart {
    flex: 1;
    padding: 8px 12px;
    background: linear-gradient(135deg, #2C4B79, #48CAE4);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.wl-btn-add-cart:hover {
    box-shadow: 0 4px 12px rgba(44,75,121,0.3);
}

.wl-btn-remove {
    padding: 8px 12px;
    background: #fde8e8;
    color: #dc3545;
    border: none;
    border-radius: 8px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
}

.wl-btn-remove:hover {
    background: #dc3545;
    color: #fff;
}

/* ===== Empty State ===== */
.wl-empty {
    text-align: center;
    padding: 4rem 1rem;
}

.wl-empty-icon {
    width: 90px;
    height: 90px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, rgba(44,75,121,0.1), rgba(72,202,228,0.1));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    color: #2C4B79;
}

.wl-empty h3 {
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 8px;
}

.wl-empty p {
    color: #888;
    font-size: 0.95rem;
    margin-bottom: 6px;
}

.wl-empty-hint {
    color: #2C4B79 !important;
    font-size: 0.9rem !important;
    margin-bottom: 20px !important;
}

.wl-empty-hint a {
    color: #48CAE4;
    text-decoration: underline;
    font-weight: 600;
}

/* ===== Responsive ===== */
@media (max-width: 767px) {
    .wl-hero-content h1 {
        font-size: 1.6rem;
    }

    .wl-summary {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }

    .wl-summary-info {
        justify-content: center;
    }

    .wl-actions {
        justify-content: center;
    }

    .wl-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 400px) {
    .wl-card {
        flex-direction: column;
    }

    .wl-card-image {
        width: 100%;
        height: 200px;
    }

    .wl-card-image img {
        min-height: auto;
    }
}
</style>
