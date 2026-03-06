<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $book->title }} - مكتبة الفقراء</title>
    @include('partials.meta-tags', [
        'metaTitle' => $book->title . ' - مكتبة الفقراء',
        'metaDescription' => Str::limit($book->description ?? $book->title . ' - اشترِ الآن من مكتبة الفقراء بأفضل سعر', 160),
        'metaImage' => $book->image ? asset($book->image) : asset('images/logo.svg'),
        'metaType' => 'product',
        'metaUrl' => route('moredetail2.page', $book->id),
    ])
    @include('partials.jsonld-book')
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
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
                        <img src="{{ asset($book->image) }}" alt="{{ $book->title }}" width="400" height="560">
                    </div>

                    <!-- Share buttons under image -->
                    <div class="v2-share">
                        <span class="v2-share-label"><i class="fas fa-share-alt"></i> مشاركة</span>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('moredetail2.page', $book->id)) }}" target="_blank" rel="noopener noreferrer" class="v2-share-btn v2-fb" title="فيسبوك"><i class="fab fa-facebook-f"></i></a>
                        <a href="{{ \App\Models\SystemSetting::getSetting('tiktok_url', 'https://www.tiktok.com/@maktabatalfokara') }}" target="_blank" rel="noopener noreferrer" class="v2-share-btn v2-tw" title="تيك توك"><i class="fab fa-tiktok"></i></a>
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
                    <div class="v2-author d-flex align-items-center gap-2">
                        <p class="mb-0">
                            <i class="fas fa-pen-fancy" style="color: var(--site-secondary)"></i>
                            @if($book->primaryAuthor)
                                <a href="{{ route('author.show', $book->primaryAuthor->id) }}">{{ $book->primaryAuthor->name }}</a>
                            @else
                                {{ $book->author }}
                            @endif
                        </p>
                        @if($book->primaryAuthor)
                            @auth
                                @php $isFollowingAuthor = \App\Models\Follow::isFollowing(Auth::id(), 'author', $book->primaryAuthor->id); @endphp
                                <button class="v2-follow-btn {{ $isFollowingAuthor ? 'following' : '' }}"
                                        onclick="toggleFollow('author', {{ $book->primaryAuthor->id }}, this)">
                                    <i class="fas {{ $isFollowingAuthor ? 'fa-user-check' : 'fa-user-plus' }}"></i>
                                    <span>{{ $isFollowingAuthor ? 'متابَع' : 'متابعة' }}</span>
                                </button>
                            @endauth
                        @endif
                    </div>

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
                        <span class="v2-price">{{ $book->price }} <small>د.م</small></span>
                        @if(($book->original_price ?? 0) > $book->price)
                        <span class="v2-original-price">{{ $book->original_price }} د.م</span>
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

                        <!-- Reading Shelf Dropdown -->
                        @auth
                        <div class="v2-shelf-dropdown" style="position:relative;display:inline-block;">
                            <button class="v2-btn-shelf {{ $shelfStatus ? 'v2-shelved' : '' }}" id="shelfToggle" type="button"
                                    title="{{ $shelfStatus ? \App\Models\ReadingShelf::STATUS_LABELS[$shelfStatus] : 'أضف لرف القراءة' }}">
                                <i class="fas fa-book-reader"></i>
                                <span class="shelf-label">{{ $shelfStatus ? \App\Models\ReadingShelf::STATUS_LABELS[$shelfStatus] : 'رف القراءة' }}</span>
                            </button>
                            <div class="v2-shelf-menu" id="shelfMenu">
                                @foreach(\App\Models\ReadingShelf::STATUS_LABELS as $key => $label)
                                <button type="button" class="v2-shelf-option {{ $shelfStatus === $key ? 'active' : '' }}"
                                        onclick="setShelfStatus('{{ $key }}', {{ $book->id }}, this)">
                                    <i class="fas {{ $key === 'want_to_read' ? 'fa-bookmark' : ($key === 'reading' ? 'fa-glasses' : 'fa-check-circle') }}"></i>
                                    {{ $label }}
                                </button>
                                @endforeach
                                @if($shelfStatus)
                                <div style="border-top:1px solid #eee;margin:4px 0;"></div>
                                <button type="button" class="v2-shelf-option v2-shelf-remove"
                                        onclick="removeFromShelf({{ $book->id }})">
                                    <i class="fas fa-times"></i> إزالة من الرف
                                </button>
                                @endif
                            </div>
                        </div>
                        @endauth
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
                    @if($book->ISBN)
                    <button class="v2-tab-btn" data-target="v2-preview"><i class="fas fa-book-open"></i> معاينة</button>
                    @endif
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
                        @php
                            $approvedReviews = $book->reviews->where('status', 'approved');
                            $reviewsList = $approvedReviews->sortByDesc('created_at');
                            $avgRating = $approvedReviews->avg('rating') ?? 0;
                            $totalReviews = $approvedReviews->count();
                        @endphp

                        <div class="v2-rating-summary" id="ratingSummary" style="{{ $totalReviews == 0 ? 'display:none' : '' }}">
                            <div class="v2-rating-big">
                                <span class="v2-rating-num" id="avgRatingNum">{{ number_format($avgRating, 1) }}</span>
                                <div class="v2-stars-big" id="avgRatingStars">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="{{ $i <= round($avgRating) ? 'fas' : 'far' }} fa-star"></i>
                                    @endfor
                                </div>
                                <small><span id="totalReviewsCount">{{ $totalReviews }}</span> تقييم</small>
                            </div>
                            <div class="v2-rating-bars" id="ratingBars">
                                @for($star = 5; $star >= 1; $star--)
                                    @php
                                        $count = $approvedReviews->where('rating', $star)->count();
                                        $pct = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                                    @endphp
                                    <div class="v2-bar-row" data-star="{{ $star }}">
                                        <span>{{ $star }} <i class="fas fa-star"></i></span>
                                        <div class="v2-bar-track"><div class="v2-bar-fill" style="width:{{ $pct }}%"></div></div>
                                        <span class="v2-bar-count">{{ $count }}</span>
                                    </div>
                                @endfor
                            </div>
                        </div>

                        <!-- Toast notification -->
                        <div class="v2-review-toast" id="reviewToast"></div>

                        <div class="v2-reviews-list" id="reviewsList">
                            @forelse($reviewsList as $review)
                            <div class="v2-review-card" data-review-id="{{ $review->id }}" data-rating="{{ $review->rating }}">
                                <div class="v2-review-header">
                                    <div class="v2-avatar">{{ mb_substr($review->user->name ?? 'م', 0, 1, 'UTF-8') }}</div>
                                    <div class="flex-grow-1">
                                        <strong>{{ $review->user->name ?? 'مستخدم' }}</strong>
                                        <div class="v2-review-stars">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="{{ $i <= $review->rating ? 'fas' : 'far' }} fa-star"></i>
                                            @endfor
                                        </div>
                                        <small>{{ $review->created_at->diffForHumans() }}</small>
                                    </div>
                                    @auth
                                    @if(auth()->id() === $review->user_id)
                                    <div class="v2-review-actions">
                                        <button class="v2-review-action-btn" onclick="editReview({{ $review->id }})" title="تعديل"><i class="fas fa-edit"></i></button>
                                        <button class="v2-review-action-btn v2-review-delete-btn" onclick="deleteReview({{ $review->id }})" title="حذف"><i class="fas fa-trash-alt"></i></button>
                                    </div>
                                    @endif
                                    @endauth
                                </div>
                                <p class="v2-review-text">{{ $review->comment }}</p>

                                <!-- Edit form (hidden by default) -->
                                @auth
                                @if(auth()->id() === $review->user_id)
                                <div class="v2-edit-form" id="editForm-{{ $review->id }}" style="display:none">
                                    <div class="mb-2">
                                        <div class="star-rating star-rating-edit" id="editStars-{{ $review->id }}">
                                            @for($s = 5; $s >= 1; $s--)
                                                <input type="radio" id="es{{ $s }}-{{ $review->id }}" name="edit_rating_{{ $review->id }}" value="{{ $s }}" {{ $review->rating == $s ? 'checked' : '' }}>
                                                <label for="es{{ $s }}-{{ $review->id }}"><i class="fas fa-star"></i></label>
                                            @endfor
                                        </div>
                                    </div>
                                    <textarea class="form-control mb-2" id="editComment-{{ $review->id }}" rows="3">{{ $review->comment }}</textarea>
                                    <div class="d-flex gap-2">
                                        <button class="v2-btn-submit v2-btn-sm" onclick="submitEdit({{ $review->id }})"><i class="fas fa-check me-1"></i>حفظ</button>
                                        <button class="v2-btn-cancel v2-btn-sm" onclick="cancelEdit({{ $review->id }})">إلغاء</button>
                                    </div>
                                </div>
                                @endif
                                @endauth

                                <!-- Helpful button -->
                                <div class="v2-review-footer">
                                    @auth
                                    <button class="v2-helpful-btn {{ $review->isLikedBy(auth()->user()) ? 'liked' : '' }}" onclick="toggleHelpful({{ $review->id }}, this)">
                                        <i class="fas fa-thumbs-up"></i>
                                        <span>مفيد</span>
                                        <span class="helpful-count">({{ $review->likes_count }})</span>
                                    </button>
                                    @else
                                    <span class="v2-helpful-info">
                                        <i class="fas fa-thumbs-up"></i> مفيد ({{ $review->likes_count }})
                                    </span>
                                    @endauth
                                </div>
                            </div>
                            @empty
                            <div class="v2-empty-state" id="emptyReviewState">
                                <i class="fas fa-star"></i>
                                <p>لا توجد تقييمات بعد — كن أول من يقيّم هذا الكتاب</p>
                            </div>
                            @endforelse
                        </div>

                        @auth
                            @php $userReview = $book->reviews->where('user_id', auth()->id())->first(); @endphp
                            <div class="v2-form-card" id="reviewFormCard" style="{{ $userReview ? 'display:none' : '' }}">
                                <h5><i class="fas fa-star me-2"></i>أضف تقييمك</h5>
                                <form id="reviewForm">
                                    @csrf
                                    <input type="hidden" name="book_id" value="{{ $book->id }}">
                                    <div class="mb-3">
                                        <label class="form-label">التقييم <span class="text-danger">*</span></label>
                                        <div class="star-rating">
                                            <input type="radio" id="s5" name="rating" value="5">
                                            <label for="s5"><i class="fas fa-star"></i></label>
                                            <input type="radio" id="s4" name="rating" value="4">
                                            <label for="s4"><i class="fas fa-star"></i></label>
                                            <input type="radio" id="s3" name="rating" value="3">
                                            <label for="s3"><i class="fas fa-star"></i></label>
                                            <input type="radio" id="s2" name="rating" value="2">
                                            <label for="s2"><i class="fas fa-star"></i></label>
                                            <input type="radio" id="s1" name="rating" value="1">
                                            <label for="s1"><i class="fas fa-star"></i></label>
                                        </div>
                                        <div class="rating-feedback mt-2"><span id="rating-text" class="text-muted">اختر عدد النجوم</span></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">تعليقك <span class="text-danger">*</span></label>
                                        <textarea name="comment" class="form-control" rows="4" placeholder="اكتب تقييمك للكتاب..." required></textarea>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input type="hidden" name="is_read" value="0">
                                        
                                        
                                    </div>
                                    <button type="submit" class="v2-btn-submit"><i class="fas fa-paper-plane me-2"></i>إرسال التقييم</button>
                                </form>
                            </div>
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
                                <h4>
                                    {{ $book->author }}
                                    @if($book->primaryAuthor)
                                        @auth
                                            @php $isFollowingAuthor = \App\Models\Follow::isFollowing(Auth::id(), 'author', $book->primaryAuthor->id); @endphp
                                            <button class="v2-follow-btn {{ $isFollowingAuthor ? 'following' : '' }}"
                                                    onclick="toggleFollow('author', {{ $book->primaryAuthor->id }}, this)">
                                                <i class="fas {{ $isFollowingAuthor ? 'fa-user-check' : 'fa-user-plus' }}"></i>
                                                <span>{{ $isFollowingAuthor ? 'متابَع' : 'متابعة' }}</span>
                                            </button>
                                        @endauth
                                    @endif
                                </h4>
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
                                @foreach($authorBooks->take(6) as $otherBook)
                                <a href="{{ route('moredetail2.page', $otherBook->id) }}" class="v2-other-book">
                                    <img src="{{ asset($otherBook->image) }}" alt="{{ $otherBook->title }}">
                                    <span>{{ Str::limit($otherBook->title, 30) }}</span>
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    @if($book->ISBN)
                    <!-- Google Books Preview -->
                    <div class="v2-tab-pane" id="v2-preview">
                        <div id="googlePreviewContainer" style="min-height:400px;">
                            <div class="text-center py-5" id="previewLoading">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-3">جاري تحميل المعاينة...</p>
                            </div>
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>

        <!-- Carousels -->
        <x-book-carousel :books="$relatedBooks" title="كتب ذات صلة" />
        @if($publisherBooks->count() > 0)
            <x-book-carousel :books="$publisherBooks" title="المزيد من {{ $book->publishingHouse->name ?? 'دار النشر' }}" />
        @endif
        @if($alsoBoughtBooks->isNotEmpty())
            <x-book-carousel :books="$alsoBoughtBooks" title="عملاء آخرون اشتروا أيضاً" />
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

    @auth
    <script>
    (function() {
        var shelfToggle = document.getElementById('shelfToggle');
        var shelfMenu = document.getElementById('shelfMenu');
        if (!shelfToggle || !shelfMenu) return;

        shelfToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            shelfMenu.classList.toggle('show');
        });
        document.addEventListener('click', function(e) {
            if (!shelfMenu.contains(e.target)) shelfMenu.classList.remove('show');
        });
    })();

    function setShelfStatus(status, bookId, btn) {
        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch('/shelf/' + bookId, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: status })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('shelfMenu').classList.remove('show');
                document.getElementById('shelfToggle').classList.add('v2-shelved');
                document.querySelector('.shelf-label').textContent = data.label;
                document.querySelectorAll('.v2-shelf-option').forEach(function(el) { el.classList.remove('active'); });
                btn.classList.add('active');
                // Add remove button if not present
                if (!document.querySelector('.v2-shelf-remove')) {
                    var sep = document.createElement('div');
                    sep.style.cssText = 'border-top:1px solid #eee;margin:4px 0;';
                    var removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'v2-shelf-option v2-shelf-remove';
                    removeBtn.innerHTML = '<i class="fas fa-times"></i> إزالة من الرف';
                    removeBtn.onclick = function() { removeFromShelf(bookId); };
                    document.getElementById('shelfMenu').appendChild(sep);
                    document.getElementById('shelfMenu').appendChild(removeBtn);
                }
            }
        });
    }

    function removeFromShelf(bookId) {
        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch('/shelf/' + bookId, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('shelfMenu').classList.remove('show');
                document.getElementById('shelfToggle').classList.remove('v2-shelved');
                document.querySelector('.shelf-label').textContent = 'رف القراءة';
                document.querySelectorAll('.v2-shelf-option').forEach(function(el) { el.classList.remove('active'); });
                var removeBtn = document.querySelector('.v2-shelf-remove');
                if (removeBtn) { removeBtn.previousElementSibling.remove(); removeBtn.remove(); }
            }
        });
    }
    </script>
    @endauth

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

        function toggleFollow(type, id, btn) {
            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            btn.disabled = true;
            fetch('/follow/' + type + '/' + id, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                document.querySelectorAll('.v2-follow-btn').forEach(function(b) {
                    b.disabled = false;
                    if (data.following) {
                        b.classList.add('following');
                        b.querySelector('i').className = 'fas fa-user-check';
                        b.querySelector('span').textContent = 'متابَع';
                    } else {
                        b.classList.remove('following');
                        b.querySelector('i').className = 'fas fa-user-plus';
                        b.querySelector('span').textContent = 'متابعة';
                    }
                });
            })
            .catch(function() { btn.disabled = false; });
        }

        // ==================== REVIEWS ====================
        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function showReviewToast(message, type) {
            var toast = document.getElementById('reviewToast');
            toast.textContent = message;
            toast.className = 'v2-review-toast show ' + (type || 'success');
            setTimeout(function() { toast.className = 'v2-review-toast'; }, 3000);
        }

        function generateStarsHtml(rating) {
            var html = '';
            for (var i = 1; i <= 5; i++) {
                html += '<i class="' + (i <= Math.round(rating) ? 'fas' : 'far') + ' fa-star"></i>';
            }
            return html;
        }

        function updateRatingSummary(summary) {
            var summaryEl = document.getElementById('ratingSummary');
            summaryEl.style.display = '';
            document.getElementById('avgRatingNum').textContent = parseFloat(summary.avg_rating).toFixed(1);
            document.getElementById('avgRatingStars').innerHTML = generateStarsHtml(summary.avg_rating);
            document.getElementById('totalReviewsCount').textContent = summary.reviews_count;
        }

        // AJAX review submission
        var reviewForm = document.getElementById('reviewForm');
        if (reviewForm) {
            reviewForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                var submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري الإرسال...';

                fetch('{{ route("reviews.store") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        // Remove empty state
                        var emptyState = document.getElementById('emptyReviewState');
                        if (emptyState) emptyState.remove();

                        // Prepend new review card
                        var review = data.review;
                        var cardHtml = '<div class="v2-review-card" data-review-id="' + review.id + '" data-rating="' + review.rating + '" style="animation:fadeInUp 0.3s ease">';
                        cardHtml += '<div class="v2-review-header">';
                        cardHtml += '<div class="v2-avatar">' + review.user_initial + '</div>';
                        cardHtml += '<div class="flex-grow-1"><strong>' + review.user_name + '</strong>';
                        cardHtml += '<div class="v2-review-stars">' + generateStarsHtml(review.rating) + '</div>';
                        cardHtml += '<small>' + review.created_at + '</small></div>';
                        cardHtml += '<div class="v2-review-actions">';
                        cardHtml += '<button class="v2-review-action-btn" onclick="editReview(' + review.id + ')" title="تعديل"><i class="fas fa-edit"></i></button>';
                        cardHtml += '<button class="v2-review-action-btn v2-review-delete-btn" onclick="deleteReview(' + review.id + ')" title="حذف"><i class="fas fa-trash-alt"></i></button>';
                        cardHtml += '</div></div>';
                        cardHtml += '<p class="v2-review-text">' + review.comment + '</p>';
                        cardHtml += '<div class="v2-edit-form" id="editForm-' + review.id + '" style="display:none">';
                        cardHtml += '<div class="mb-2"><div class="star-rating star-rating-edit" id="editStars-' + review.id + '">';
                        for (var s = 5; s >= 1; s--) {
                            cardHtml += '<input type="radio" id="es' + s + '-' + review.id + '" name="edit_rating_' + review.id + '" value="' + s + '"' + (review.rating == s ? ' checked' : '') + '>';
                            cardHtml += '<label for="es' + s + '-' + review.id + '"><i class="fas fa-star"></i></label>';
                        }
                        cardHtml += '</div></div>';
                        cardHtml += '<textarea class="form-control mb-2" id="editComment-' + review.id + '" rows="3">' + review.comment + '</textarea>';
                        cardHtml += '<div class="d-flex gap-2">';
                        cardHtml += '<button class="v2-btn-submit v2-btn-sm" onclick="submitEdit(' + review.id + ')"><i class="fas fa-check me-1"></i>حفظ</button>';
                        cardHtml += '<button class="v2-btn-cancel v2-btn-sm" onclick="cancelEdit(' + review.id + ')">إلغاء</button>';
                        cardHtml += '</div></div>';
                        cardHtml += '<div class="v2-review-footer"><button class="v2-helpful-btn" onclick="toggleHelpful(' + review.id + ', this)">';
                        cardHtml += '<i class="fas fa-thumbs-up"></i> <span>مفيد</span> <span class="helpful-count">(0)</span></button></div>';
                        cardHtml += '</div>';

                        document.getElementById('reviewsList').insertAdjacentHTML('afterbegin', cardHtml);

                        // Update summary
                        updateRatingSummary(data.summary);

                        // Hide form
                        document.getElementById('reviewFormCard').style.display = 'none';
                        showReviewToast(data.message, 'success');
                    } else {
                        showReviewToast(data.message || 'حدث خطأ', 'error');
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>إرسال التقييم';
                })
                .catch(function() {
                    showReviewToast('حدث خطأ في الاتصال', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>إرسال التقييم';
                });
            });
        }

        // Edit review
        function editReview(id) {
            var card = document.querySelector('[data-review-id="' + id + '"]');
            card.querySelector('.v2-review-text').style.display = 'none';
            card.querySelector('.v2-review-footer').style.display = 'none';
            document.getElementById('editForm-' + id).style.display = 'block';
        }

        function cancelEdit(id) {
            var card = document.querySelector('[data-review-id="' + id + '"]');
            card.querySelector('.v2-review-text').style.display = '';
            card.querySelector('.v2-review-footer').style.display = '';
            document.getElementById('editForm-' + id).style.display = 'none';
        }

        function submitEdit(id) {
            var rating = document.querySelector('input[name="edit_rating_' + id + '"]:checked');
            var comment = document.getElementById('editComment-' + id).value.trim();

            if (!rating || !comment) {
                showReviewToast('يرجى ملء جميع الحقول', 'error');
                return;
            }

            fetch('/reviews/' + id, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ rating: rating.value, comment: comment })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    var card = document.querySelector('[data-review-id="' + id + '"]');
                    card.dataset.rating = data.review.rating;
                    card.querySelector('.v2-review-text').textContent = data.review.comment;
                    card.querySelector('.v2-review-stars').innerHTML = generateStarsHtml(data.review.rating);
                    cancelEdit(id);
                    updateRatingSummary(data.summary);
                    showReviewToast(data.message, 'success');
                } else {
                    showReviewToast(data.message || 'حدث خطأ', 'error');
                }
            })
            .catch(function() { showReviewToast('حدث خطأ في الاتصال', 'error'); });
        }

        // Delete review
        function deleteReview(id) {
            if (!confirm('هل أنت متأكد من حذف تقييمك؟')) return;

            fetch('/reviews/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    var card = document.querySelector('[data-review-id="' + id + '"]');
                    card.style.animation = 'fadeOutUp 0.3s ease';
                    setTimeout(function() {
                        card.remove();
                        // Show empty state if no reviews left
                        if (document.querySelectorAll('.v2-review-card').length === 0) {
                            document.getElementById('reviewsList').innerHTML = '<div class="v2-empty-state" id="emptyReviewState"><i class="fas fa-star"></i><p>لا توجد تقييمات بعد — كن أول من يقيّم هذا الكتاب</p></div>';
                            document.getElementById('ratingSummary').style.display = 'none';
                        } else {
                            updateRatingSummary(data.summary);
                        }
                    }, 300);
                    // Show form again
                    var formCard = document.getElementById('reviewFormCard');
                    if (formCard) formCard.style.display = '';
                    showReviewToast(data.message, 'success');
                }
            })
            .catch(function() { showReviewToast('حدث خطأ في الاتصال', 'error'); });
        }

        // Toggle helpful
        function toggleHelpful(id, btn) {
            if (!window.isLoggedIn) {
                window.location.href = window.loginUrl;
                return;
            }
            btn.disabled = true;
            fetch('/reviews/' + id + '/helpful', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    btn.classList.toggle('liked', data.liked);
                    btn.querySelector('.helpful-count').textContent = '(' + data.likes_count + ')';
                }
                btn.disabled = false;
            })
            .catch(function() { btn.disabled = false; });
        }
    </script>

    @if($book->ISBN)
    <script>
    (function() {
        var previewLoaded = false;
        var isbn = "{{ $book->ISBN }}";
        var noPreviewHtml = '<div class="text-center py-5">' +
            '<i class="fas fa-book" style="font-size:3rem;color:#ccc;"></i>' +
            '<p class="mt-3 text-muted">لا توجد معاينة متاحة لهذا الكتاب</p>' +
            '<a href="https://books.google.com/books?q=isbn:' + isbn + '" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm mt-2">' +
            '<i class="fas fa-external-link-alt me-1"></i> البحث في Google Books</a></div>';

        document.querySelectorAll('.v2-tab-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (this.dataset.target === 'v2-preview' && !previewLoaded) {
                    previewLoaded = true;
                    loadGooglePreview();
                }
            });
        });

        function loadGooglePreview() {
            var container = document.getElementById('googlePreviewContainer');
            // Timeout: if nothing loads in 8 seconds, show fallback
            var timeout = setTimeout(function() {
                container.innerHTML = noPreviewHtml;
            }, 8000);

            // Dynamically load the script to catch load errors
            var script = document.createElement('script');
            script.src = 'https://www.google.com/books/jsapi.js';
            script.onload = function() {
                try {
                    google.books.load();
                    google.books.setOnLoadCallback(function() {
                        clearTimeout(timeout);
                        var viewer = new google.books.DefaultViewer(container);
                        viewer.load('ISBN:' + isbn, function() {
                            container.innerHTML = noPreviewHtml;
                        });
                    });
                } catch (e) {
                    clearTimeout(timeout);
                    container.innerHTML = noPreviewHtml;
                }
            };
            script.onerror = function() {
                clearTimeout(timeout);
                container.innerHTML = noPreviewHtml;
            };
            document.head.appendChild(script);
        }
    })();
    </script>
    @endif
</body>
</html>
