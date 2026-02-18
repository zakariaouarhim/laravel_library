<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $book->title }} - مكتبة الفقراء</title>
    @include('partials.meta-tags', [
        'metaTitle' => $book->title . ' - مكتبة الفقراء',
        'metaDescription' => Str::limit($book->description ?? $book->title . ' - اشترِ الآن من مكتبة الفقراء بأفضل سعر', 160),
        'metaImage' => $book->cover_image ? asset('storage/' . $book->cover_image) : asset('images/logo.svg'),
        'metaType' => 'product',
        'metaUrl' => route('moredetail2.page', $book->id),
    ])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" integrity="sha384-gXt9imSW0VcJVHezoNQsP+TNrjYXoGcrqBZJpry9zJt8PCQjobwmhMGaDHTASo9N" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
    <link rel="stylesheet" href="{{ asset('css/moredetail-V2.css') }}">
    <link rel="stylesheet" href="{{ asset('css/carouselstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="auth-user" content="true">
    @endauth
    
</head>
<body>
    @include('header')

    <!-- Breadcrumb Strip -->
    <div class="v2-breadcrumb">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}"><i class="fas fa-home"></i> الرئيسية</a></li>
                    @if($book->category)
                        @if($book->category->parent)
                            <li class="breadcrumb-item"><a href="{{ route('by-category', $book->category->parent->id) }}">{{ $book->category->parent->name }}</a></li>
                        @endif
                        <li class="breadcrumb-item"><a href="{{ route('by-category', $book->category->id) }}">{{ $book->category->name }}</a></li>
                    @endif
                    <li class="breadcrumb-item active">{{ Str::limit($book->title, 40) }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Main Product Section -->
    <div class="v2-page">
        <div class="container">
            <div class="v2-product-grid">

                <!-- Image Column (sticky) -->
                <div class="v2-image-col">
                    <div class="v2-image-card">
                        @if($book->discount ?? 0 > 0)
                        <span class="v2-badge-discount">خصم {{ $book->discount }}%</span>
                        @endif
                        @if($book->is_new ?? false)
                        <span class="v2-badge-new">جديد</span>
                        @endif
                        <img src="{{ asset($book->image) }}" alt="{{ $book->title }}">
                    </div>

                    <!-- Share buttons under image -->
                    <div class="v2-share">
                        <span class="v2-share-label"><i class="fas fa-share-alt"></i> مشاركة</span>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('moredetail2.page', $book->id)) }}" target="_blank" rel="noopener noreferrer" class="v2-share-btn v2-fb" title="فيسبوك"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://twitter.com/intent/tiktok?text={{ urlencode($book->title) }}&url={{ urlencode(route('moredetail2.page', $book->id)) }}" target="_blank" rel="noopener noreferrer" class="v2-share-btn v2-tw" title="tiktok"><i class="fab fa-tiktok"></i></a>
                        <a href="https://api.whatsapp.com/send?text={{ urlencode($book->title . ' ' . route('moredetail2.page', $book->id)) }}" target="_blank" rel="noopener noreferrer" class="v2-share-btn v2-wa" title="واتساب"><i class="fab fa-whatsapp"></i></a>
                        <button class="v2-share-btn v2-copy" title="نسخ الرابط" onclick="copyBookLink()"><i class="fas fa-link"></i></button>
                    </div>
                </div>

                <!-- Info Column -->
                <div class="v2-info-col">

                    <!-- Category pills -->
                    <div class="v2-categories">
                        @if($book->category)
                            @if($book->category->parent)
                                <a href="{{ route('by-category', $book->category->parent->id) }}" class="v2-cat-pill">{{ $book->category->parent->name }}</a>
                            @endif
                            <a href="{{ route('by-category', $book->category->id) }}" class="v2-cat-pill v2-cat-sub">{{ $book->category->name }}</a>
                        @else
                            <span class="v2-cat-pill">غير مصنف</span>
                        @endif
                    </div>

                    <!-- Title -->
                    <h1 class="v2-title">{{ $book->title }}</h1>

                    <!-- Author -->
                    <p class="v2-author">
                        <i class="fas fa-pen-fancy"></i>
                        @if($book->primaryAuthor)
                            <a href="{{ route('author.show', $book->primaryAuthor->id) }}">{{ $book->primaryAuthor->name }}</a>
                        @else
                            {{ $book->author }}
                        @endif
                    </p>

                    <!-- Rating summary -->
                    @if($book->reviews_count > 0)
                    <div class="v2-rating">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="{{ $i <= round($book->average_rating) ? 'fas' : 'far' }} fa-star"></i>
                        @endfor
                        <span class="v2-rating-score">{{ number_format($book->average_rating, 1) }}</span>
                        <span class="v2-rating-count">({{ $book->reviews_count }} تقييم)</span>
                    </div>
                    @endif

                    <hr class="v2-divider">

                    <!-- Price -->
                    <div class="v2-price-block">
                        <span class="v2-price">{{ $book->price }} <small>ر.س</small></span>
                        @if(($book->original_price ?? 0) > $book->price)
                        <span class="v2-original-price">{{ $book->original_price }} ر.س</span>
                        @endif
                        @if($book->discount ?? 0 > 0)
                        <span class="v2-discount-pill">وفّر {{ $book->discount }}%</span>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="v2-actions">
                        <div class="v2-qty-wrap">
                            <button class="v2-qty-btn" onclick="this.nextElementSibling.stepDown()">−</button>
                            <input type="number" class="v2-qty-input" value="1" min="1" aria-label="عدد النسخ">
                            <button class="v2-qty-btn" onclick="this.previousElementSibling.stepUp()">+</button>
                        </div>
                        <button class="v2-btn-cart"
                                id="addToCartButton"
                                data-book-id="{{ $book->id }}"
                                data-title="{{ $book->title }}"
                                data-price="{{ $book->price }}"
                                data-image="{{ $book->image }}"
                                onclick="addToCartM({{ $book->id }})">
                            <i class="fas fa-shopping-cart"></i> أضف إلى السلة
                        </button>
                        <button class="v2-btn-wishlist {{ in_array($book->id, $wishlistBookIds) ? 'v2-wishlisted' : '' }}"
                                title="{{ in_array($book->id, $wishlistBookIds) ? 'إزالة من المفضلة' : 'أضف للمفضلة' }}"
                                onclick="toggleWishlist({{ $book->id }}, this)">
                            <i class="{{ in_array($book->id, $wishlistBookIds) ? 'fas' : 'far' }} fa-heart"></i>
                        </button>
                    </div>

                    <!-- Delivery -->
                    <div class="v2-delivery">
                        <i class="fas fa-truck"></i>
                        <div>
                            <strong>توصيل سريع</strong>
                            <span>يصلك خلال 2-3 أيام عمل</span>
                        </div>
                    </div>

                    <hr class="v2-divider">

                    <!-- Book Stats -->
                    <div class="v2-stats">
                        <div class="v2-stat">
                            <i class="fas fa-globe-africa"></i>
                            <div>
                                <span class="v2-stat-label">اللغة</span>
                                <span class="v2-stat-value">{{ $book->Langue }}</span>
                            </div>
                        </div>
                        <div class="v2-stat">
                            <i class="fas fa-book-open"></i>
                            <div>
                                <span class="v2-stat-label">الصفحات</span>
                                <span class="v2-stat-value">{{ $book->Page_Num }}</span>
                            </div>
                        </div>
                        @if($book->publishingHouse)
                        <div class="v2-stat">
                            <i class="fas fa-building"></i>
                            <div>
                                <span class="v2-stat-label">دار النشر</span>
                                <a href="{{ route('publisher.show', $book->publishing_house_id) }}" class="v2-stat-value v2-stat-link">{{ $book->publishingHouse->name }}</a>
                            </div>
                        </div>
                        @endif
                        @if($book->ISBN)
                        <div class="v2-stat">
                            <i class="fas fa-barcode"></i>
                            <div>
                                <span class="v2-stat-label">ISBN</span>
                                <span class="v2-stat-value">{{ $book->ISBN }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="v2-tabs-section">
                <div class="v2-tab-nav" id="v2TabNav">
                    <button class="v2-tab-btn active" data-target="v2-desc"><i class="fas fa-align-right"></i> الوصف</button>
                    <button class="v2-tab-btn" data-target="v2-details"><i class="fas fa-list-ul"></i> تفاصيل</button>
                    <button class="v2-tab-btn" data-target="v2-reviews"><i class="fas fa-star"></i> التقييمات @if($book->reviews_count > 0)<span class="v2-tab-badge">{{ $book->reviews_count }}</span>@endif</button>
                    <button class="v2-tab-btn" data-target="v2-quotes"><i class="fas fa-quote-right"></i> اقتباسات @if(isset($book->quotes) && $book->quotes->count() > 0)<span class="v2-tab-badge">{{ $book->quotes->count() }}</span>@endif</button>
                    <button class="v2-tab-btn" data-target="v2-author"><i class="fas fa-user-edit"></i> عن الكاتب</button>
                </div>

                <div class="v2-tab-content">

                    <!-- Description -->
                    <div class="v2-tab-pane active" id="v2-desc">
                        <p class="v2-description">{{ $book->description }}</p>
                    </div>

                    <!-- Details -->
                    <div class="v2-tab-pane" id="v2-details">
                        <div class="v2-details-grid">
                            @if($book->ISBN)
                            <div class="v2-detail-row"><span>ISBN</span><span>{{ $book->ISBN }}</span></div>
                            @endif
                            <div class="v2-detail-row"><span>تاريخ الإضافة</span><span>{{ $book->created_at->format('d / m / Y') }}</span></div>
                            <div class="v2-detail-row"><span>اللغة</span><span>{{ $book->Langue }}</span></div>
                            <div class="v2-detail-row"><span>عدد الصفحات</span><span>{{ $book->Page_Num }}</span></div>
                            @if($book->publishingHouse)
                            <div class="v2-detail-row"><span>دار النشر</span><a href="{{ route('publisher.show', $book->publishing_house_id) }}" class="v2-stat-link">{{ $book->publishingHouse->name }}</a></div>
                            @endif
                        </div>
                    </div>

                    <!-- Reviews -->
                    <div class="v2-tab-pane" id="v2-reviews">
                        @if($book->reviews->count() > 0)
                        <div class="v2-rating-summary">
                            <div class="v2-rating-big">
                                <span class="v2-rating-num">{{ number_format($book->average_rating, 1) }}</span>
                                <div class="v2-stars-big">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="{{ $i <= round($book->average_rating) ? 'fas' : 'far' }} fa-star"></i>
                                    @endfor
                                </div>
                                <small>{{ $book->reviews_count }} تقييم</small>
                            </div>
                            <div class="v2-rating-bars">
                                @for($star = 5; $star >= 1; $star--)
                                    @php
                                        $count = $book->reviews->where('rating', $star)->count();
                                        $pct = $book->reviews_count > 0 ? ($count / $book->reviews_count) * 100 : 0;
                                    @endphp
                                    <div class="v2-bar-row">
                                        <span>{{ $star }} <i class="fas fa-star"></i></span>
                                        <div class="v2-bar-track"><div class="v2-bar-fill" style="width:{{ $pct }}%"></div></div>
                                        <span class="v2-bar-count">{{ $count }}</span>
                                    </div>
                                @endfor
                            </div>
                        </div>
                        @endif

                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show mt-3"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                        @endif

                        <div class="v2-reviews-list">
                            @forelse($book->reviews->sortByDesc('created_at') as $review)
                            <div class="v2-review-card">
                                <div class="v2-review-header">
                                    <div class="v2-avatar">{{ mb_substr($review->user->name ?? 'م', 0, 1, 'UTF-8') }}</div>
                                    <div>
                                        <strong>{{ $review->user->name ?? 'مستخدم' }}</strong>
                                        <div class="v2-review-stars">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="{{ $i <= $review->rating ? 'fas' : 'far' }} fa-star"></i>
                                            @endfor
                                        </div>
                                        <small>{{ $review->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                                <p class="v2-review-text">{{ $review->comment }}</p>
                            </div>
                            @empty
                            <div class="v2-empty-state">
                                <i class="fas fa-star"></i>
                                <p>لا توجد تقييمات بعد — كن أول من يقيّم هذا الكتاب</p>
                            </div>
                            @endforelse
                        </div>

                        @auth
                            @php $userReview = $book->reviews->where('user_id', auth()->id())->first(); @endphp
                            @if(!$userReview)
                            <div class="v2-form-card">
                                <h5><i class="fas fa-star me-2"></i>أضف تقييمك</h5>
                                <form action="{{ route('reviews.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="book_id" value="{{ $book->id }}">
                                    <div class="mb-3">
                                        <label class="form-label">التقييم <span class="text-danger">*</span></label>
                                        <div class="star-rating">
                                            <input type="radio" id="s5" name="rating" value="5" {{ old('rating') == 5 ? 'checked' : '' }}>
                                            <label for="s5"><i class="fas fa-star"></i></label>
                                            <input type="radio" id="s4" name="rating" value="4" {{ old('rating') == 4 ? 'checked' : '' }}>
                                            <label for="s4"><i class="fas fa-star"></i></label>
                                            <input type="radio" id="s3" name="rating" value="3" {{ old('rating') == 3 ? 'checked' : '' }}>
                                            <label for="s3"><i class="fas fa-star"></i></label>
                                            <input type="radio" id="s2" name="rating" value="2" {{ old('rating') == 2 ? 'checked' : '' }}>
                                            <label for="s2"><i class="fas fa-star"></i></label>
                                            <input type="radio" id="s1" name="rating" value="1" {{ old('rating') == 1 ? 'checked' : '' }}>
                                            <label for="s1"><i class="fas fa-star"></i></label>
                                        </div>
                                        <div class="rating-feedback mt-2"><span id="rating-text" class="text-muted">اختر عدد النجوم</span></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">تعليقك <span class="text-danger">*</span></label>
                                        <textarea name="comment" class="form-control" rows="4" placeholder="اكتب تقييمك للكتاب..." required>{{ old('comment') }}</textarea>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input type="hidden" name="is_read" value="0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="is_read" name="is_read" value="1">
                                        <label class="form-check-label" for="is_read"><i class="fas fa-book-open me-1"></i> أؤكد أنني قرأت هذا الكتاب</label>
                                    </div>
                                    <button type="submit" class="v2-btn-submit"><i class="fas fa-paper-plane me-2"></i>إرسال التقييم</button>
                                </form>
                            </div>
                            @else
                            <div class="alert alert-info mt-3"><i class="fas fa-info-circle me-2"></i>لقد قمت بتقييم هذا الكتاب بـ {{ $userReview->rating }} نجوم.</div>
                            @endif
                        @else
                        <div class="v2-login-prompt"><i class="fas fa-user-lock"></i><span>يرجى <a href="{{ route('login2.page') }}">تسجيل الدخول</a> لإضافة تقييمك</span></div>
                        @endauth
                    </div>

                    <!-- Quotes -->
                    <div class="v2-tab-pane" id="v2-quotes">
                        @if(session('quote_success'))
                        <div class="alert alert-success alert-dismissible fade show mb-3"><i class="fas fa-check-circle me-2"></i>{{ session('quote_success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                        @endif

                        @if(isset($book->quotes) && $book->quotes->count() > 0)
                        <div class="v2-quotes-grid">
                            @foreach($book->quotes->sortByDesc('created_at') as $quote)
                            <div class="v2-quote-card">
                                <i class="fas fa-quote-right v2-quote-icon"></i>
                                <p class="v2-quote-text">"{{ $quote->text }}"</p>
                                <div class="v2-quote-footer">
                                    <div class="v2-avatar v2-avatar-sm">{{ mb_substr($quote->user->name ?? 'م', 0, 1, 'UTF-8') }}</div>
                                    <span>{{ $quote->user->name ?? 'مستخدم' }}</span>
                                    <span class="ms-auto text-muted small">{{ $quote->created_at->diffForHumans() }}</span>
                                    @auth
                                    <form class="d-inline" action="{{ route('quotes.toggle-like', $quote->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="v2-like-btn {{ $quote->isLikedBy(auth()->user()) ? 'liked' : '' }}">
                                            <i class="{{ $quote->isLikedBy(auth()->user()) ? 'fas' : 'far' }} fa-heart"></i> {{ $quote->likes_count ?? 0 }}
                                        </button>
                                    </form>
                                    @else
                                    <span class="text-muted small"><i class="far fa-heart"></i> {{ $quote->likes_count ?? 0 }}</span>
                                    @endauth
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="v2-empty-state">
                            <i class="fas fa-quote-right"></i>
                            <p>لا توجد اقتباسات بعد — كن أول من يشارك اقتباسًا</p>
                        </div>
                        @endif

                        @auth
                        <div class="v2-form-card mt-4">
                            <h5><i class="fas fa-quote-right me-2"></i>أضف اقتباسًا جديدًا</h5>
                            <form action="{{ route('quotes.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="book_id" value="{{ $book->id }}">
                                <div class="mb-3">
                                    <textarea name="text" class="form-control" rows="4" placeholder="اكتب الاقتباس هنا..." required>{{ old('text') }}</textarea>
                                </div>
                                <button type="submit" class="v2-btn-submit"><i class="fas fa-plus me-2"></i>إضافة الاقتباس</button>
                            </form>
                        </div>
                        @else
                        <div class="v2-login-prompt mt-3"><i class="fas fa-user-lock"></i><span>يرجى <a href="{{ route('login2.page') }}">تسجيل الدخول</a> لإضافة اقتباسات</span></div>
                        @endauth
                    </div>

                    <!-- Author Bio -->
                    <div class="v2-tab-pane" id="v2-author">
                        <div class="v2-author-card">
                            <div class="v2-author-avatar">
                                @if(isset($book->primaryAuthor) && $book->primaryAuthor && $book->primaryAuthor->profile_image)
                                    <img src="{{ Storage::url($book->primaryAuthor->profile_image) }}" alt="{{ $book->author }}">
                                @else
                                    <div class="v2-avatar v2-avatar-lg">{{ mb_substr($book->author ?? 'م', 0, 1, 'UTF-8') }}</div>
                                @endif
                            </div>
                            <div class="v2-author-info">
                                <h4>{{ $book->author }}</h4>
                                <span class="v2-author-role"><i class="fas fa-pen-fancy"></i> مؤلف</span>
                            </div>
                        </div>
                        @if(isset($book->primaryAuthor) && $book->primaryAuthor && $book->primaryAuthor->biography)
                            <p class="v2-author-bio">{{ $book->primaryAuthor->biography }}</p>
                        @else
                            <div class="v2-empty-state"><i class="fas fa-user-edit"></i><p>لا توجد نبذة عن الكاتب بعد</p></div>
                        @endif

                        @if(isset($authorBooks) && $authorBooks->count() > 0)
                        <div class="v2-other-books">
                            <h6>كتب أخرى للمؤلف</h6>
                            <div class="v2-other-books-list">
                                @foreach($authorBooks->take(4) as $otherBook)
                                <a href="{{ route('moredetail2.page', $otherBook->id) }}" class="v2-other-book">
                                    <img src="{{ asset($otherBook->image) }}" alt="{{ $otherBook->title }}">
                                    <span>{{ Str::limit($otherBook->title, 30) }}</span>
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>

        <!-- Carousels -->
        <x-book-carousel :books="$relatedBooks" title="كتب ذات صلة" />
        @if($publisherBooks->count() > 0)
            <x-book-carousel :books="$publisherBooks" title="المزيد من {{ $book->publishingHouse->name ?? 'دار النشر' }}" />
        @endif
    </div>

    <footer>
        @include('footer')
    </footer>

    <script src="{{ asset('js/moredetail.js') }}"></script>
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/carousel.js') }}"></script>
    <script src="{{ asset('js/card.js') }}"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>

    <script>
        // Tab switching
        document.querySelectorAll('.v2-tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.v2-tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.v2-tab-pane').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                document.getElementById(this.dataset.target).classList.add('active');
            });
        });

        function copyBookLink() {
            navigator.clipboard.writeText(window.location.href).then(function() {
                var btn = document.querySelector('.v2-copy');
                btn.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => btn.innerHTML = '<i class="fas fa-link"></i>', 2000);
            });
        }
    </script>
</body>
</html>
