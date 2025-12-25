<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الكتاب</title>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/moredetailstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/carouselstyle.css') }}">
    
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" integrity="sha384-gXt9imSW0VcJVHezoNQsP+TNrjYXoGcrqBZJpry9zJt8PCQjobwmhMGaDHTASo9N" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    @include('header')
   
    <div class="Layout-moredetail">
    <div class="container-fluid py-5">
        <div class="row g-0">
            <!-- Book Image Section -->
            <div class="col-md-5 d-flex align-items-center justify-content-center position-relative">
                <img src="{{ asset($book->image) }}" alt="{{ $book->title }}" class="img-fluid rounded shadow" aria-describedby="book-title">
            </div>

            <!-- Book Information Section -->
            <div class="col-md-7 p-4">
                <h1 id="book-title" class="fw-bold mb-3">{{ $book->title }}</h1>
                <p class="text-muted">{{ $book->author }}</p>

                <div class="d-flex align-items-center mb-3">
                    <span class="fs-4 text-primary fw-bold">{{ $book->price }} ريال</span>
                    <span class="badge bg-danger ms-3">10% خصم</span>
                </div>

                <div class="mb-4">
                    @if($book->category)
                    @if($book->category->parent)
                        {{-- Display parent category --}}
                        <span class="badge bg-primary">{{ $book->category->parent->name }}</span>
                        {{-- Display child category (current category) --}}
                        <span class="badge bg-secondary">{{ $book->category->name }}</span>
                    @else
                        {{-- If category has no parent, it's a main category --}}
                        <span class="badge bg-primary">{{ $book->category->name }}</span>
                    @endif
                @else
                    {{-- Fallback if no category is assigned --}}
                    <span class="badge bg-warning">غير مصنف</span>
                @endif
                </div>

                <p class="mb-4">{{ $book->description }}</p>
                {{--  --}}
                
                {{--  --}}

                <div class="d-flex align-items-center mb-4">
                    <div class="input-group" style="max-width: 120px;">
                        <input type="number" class="form-control text-center" value="1" min="1" aria-label="عدد النسخ">
                    </div>
                    
                    <button class="btn btn-primary ms-3" 
                            id="addToCartButton" 
                            aria-label="أضف الكتاب للسلة"
                            data-book-id="{{ $book->id }}" 
                            data-title="{{ $book->title }}" 
                            data-price="{{ $book->price }}" 
                            data-image="{{ $book->image }}"
                            onclick="addToCartM({{ $book->id }})">
                        أضف إلى السلة
                    </button>
                </div>

                <div class="row g-2">
                    <div class="col-sm-4">
                        <div class="p-2 border rounded">
                            <span class="fw-bold"><i class="fas fa-globe-africa me-2"></i>اللغة: </span> {{ $book->Langue }}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-2 border rounded">
                            <span class="fw-bold"><i class="fas fa-book-open me-2"></i>عدد الصفحات:</span> {{ $book->Page_Num }}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-2 border rounded">
                            <span class="fw-bold"><i class="fas fa-building me-2"></i>دار النشر:</span> {{ $book->Publishing_House }} 
                        </div>
                    </div>
                    <div class="delivery-option d-flex align-items-center">
                        <i class="fas fa-truck me-3 text-primary"></i>
                        <div>
                            <strong>توصيل سريع</strong>
                            <p class="mb-0 text-muted">يصلك خلال 2-5 أيام عمل</p>
                        </div>
                    </div>
                        

                </div>
            </div>
        </div>

        <!-- Book Details Tabs -->
        <div class="mt-5">
            <ul class="nav nav-tabs" id="bookDetailsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab" aria-controls="description" aria-selected="true" style="color: black">
                        <i class="fas fa-info-circle me-2"></i>الوصف</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="false" style="color: black">
                        <i class="fas fa-list-ul me-2"></i>تفاصيل إضافية</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab" aria-controls="reviews" aria-selected="false" style="color: black">
                        <i class="fas fa-star me-2"></i>التقييمات
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#quotes" type="button" role="tab" aria-controls="quotes" aria-selected="false" style="color: black">
                        <i class="fa-solid fa-quote-right"></i> اقتباسات   
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="biography-tab" data-bs-toggle="tab" data-bs-target="#biography" type="button" role="tab" aria-controls="biography" aria-selected="false" style="color: black">
                        <i class="fas fa-user-edit mb-3" style="color: black;"></i> نبذة عن الكاتب

                    </button>
                </li>
            </ul>
            <div class="tab-content border rounded-bottom p-3" id="bookDetailsTabsContent">
                <div class="tab-pane fade show active" id="description" role="tabpanel" aria-labelledby="description-tab">
                    <p>{{ $book->description }}</p>
                </div>
                 <!-- New Author Bio Tab -->
                <div class="tab-pane fade" id="biography" role="tabpanel" aria-labelledby="author-bio-tab">
                    <div class="author-bio-section">
                        <div class="d-flex align-items-start mb-3">
                            <div class="author-avatar me-3">
                                
                                @if(isset($book->primaryAuthor) && $book->primaryAuthor && $book->primaryAuthor->profile_image)
                                <img src="{{ asset($book->primaryAuthor->profile_image) }}" alt="{{ $book->author }}" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                                @else
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 2rem;">
                                        {{ mb_substr($book->author, 0, 1, 'UTF-8') }}
                                    </div>
                                @endif
                            </div>
                            <div>
                                <h4 class="mb-2">{{ $book->author }}</h4>
                                <div class="text-muted">
                                    <i class="fas fa-pen-fancy me-1"></i>
                                    مؤلف
                                </div>
                            </div>
                        </div>
                        
                        <div class="author-description">
                            @if(isset($book->primaryAuthor) && $book->primaryAuthor && $book->primaryAuthor->biography)
                            <p>{{ $book->primaryAuthor->biography }}</p>
                             @else
                                <div class="text-center p-4 bg-light rounded">
                                    <i class="fas fa-user-edit text-muted mb-3" style="font-size: 3rem;"></i>
                                    <h5 class="text-muted">لا توجد معلومات متاحة</h5>
                                    <p class="text-muted mb-0">لم يتم إضافة نبذة عن الكاتب بعد</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Optional: Author's other books section -->
                        @if(isset($authorBooks) && $authorBooks->count() > 1)
                        <div class="author-other-books mt-4">
                            <h5 class="mb-3">
                                <i class="fas fa-books me-2"></i>
                                كتب أخرى للمؤلف
                            </h5>
                            <div class="list-group">
                                @foreach($authorBooks->where('id', '!=', $book->id)->take(3) as $otherBook)
                                <a href="{{ route('moredetail.page', ['id' => $otherBook->id]) }}" 
                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="fa-solid fa-book text-primary me-3"></i>
                                        <div>
                                            <h6 class="mb-1">{{ $otherBook->title }}</h6>
                                            <small class="text-muted">
                                                @if($otherBook->primaryAuthor)
                                                    {{ $otherBook->primaryAuthor->name }}
                                                @else
                                                    {{ $otherBook->author ?? 'مؤلف غير معروف' }}
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                    
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="tab-pane fade" id="details" role="tabpanel" aria-labelledby="details-tab">
                    <table class="table table-bordered">
                        <tr>
                            <th>ISBN</th>
                            <td>{{ $book->ISBN }}</td>
                        </tr>
                        <tr>
                            <th>تاريخ النشر</th>
                            <td>{{ $book->created_at }}</td>
                        </tr>
                        <tr>
                            <th>الوزن</th>
                            <td>350 جرام</td>
                        </tr>
                        <tr>
                            <th>الأبعاد</th>
                            <td>14 × 21 سم</td>
                        </tr>
                        <tr>
                            <th>نوع الغلاف</th>
                            <td>غلاف ورقي</td>
                        </tr>
                    </table>
                </div>
                <!-- quotes -->
                <!-- Quotes Section -->
<div class="tab-pane fade" id="quotes" role="tabpanel" aria-labelledby="quotes-tab">
    <!-- Success/Error Messages for Quotes -->
    @if(session('quote_success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('quote_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('quote_error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('quote_error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Quotes Display -->
    <div class="quotes-section mb-4">
        @if(isset($book->quotes) && $book->quotes->count() > 0)
            <div class="row">
                @foreach ($book->quotes->sortByDesc('created_at') as $quote)
                <div class="col-md-6 mb-4">
                    <div class="quote-card h-100 border rounded p-4 position-relative bg-light">
                        <i class="fa-solid fa-quote-right position-absolute text-primary opacity-25" 
                           style="font-size: 3rem; top: 15px; right: 20px;"></i>
                           <br>
                        
                        <div class="quote-content mb-3" style="padding-top: 20px;">
                            <p class="mb-3 fst-italic" style="font-size: 1.1rem; line-height: 1.6;">
                                "{{ $quote->text }}"
                            </p>
                            
                            
                        </div>
                        
                        <div class="quote-footer d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="avatar me-2">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                        style="width: 32px; height: 32px; font-size: 0.9rem;">
                                        {{ mb_substr($quote->user->name ?? 'م', 0, 1, 'UTF-8') }}
                                    </div>
                                </div>
                                <div>
                                    <small class="text-muted">{{ $quote->user->name ?? 'مستخدم' }}</small>
                                </div>
                            </div>
                            
                            <div class="quote-actions">
                                <small class="text-muted me-2">{{ $quote->created_at->diffForHumans() }}</small>
                                
                                <!-- Like/Unlike button -->
                                @auth
                                <form class="d-inline" action="{{ route('quotes.toggle-like', $quote->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary border-0">
                                        <i class="{{ $quote->isLikedBy(auth()->user()) ? 'fas' : 'far' }} fa-heart"></i>
                                        <span class="ms-1">{{ $quote->likes_count ?? 0 }}</span>
                                    </button>
                                </form>
                                @endauth
                                
                                @guest
                                <span class="text-muted">
                                    <i class="far fa-heart"></i>
                                    <span class="ms-1">{{ $quote->likes_count ?? 0 }}</span>
                                </span>
                                @endguest
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center p-5">
                <i class="fa-solid fa-quote-right text-muted mb-3" style="font-size: 4rem;"></i>
                <h5 class="text-muted">لا توجد اقتباسات بعد</h5>
                <p class="text-muted">كن أول من يشارك اقتباس من هذا الكتاب</p>
            </div>
        @endif
    </div>

    <!-- Add Quote Form -->
    @auth
    @php
        // Check if user has already read the book or has permission to add quotes
        $canAddQuote = true; // You can add your logic here
    @endphp
    
    @if($canAddQuote)
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fa-solid fa-quote-right me-2"></i>أضف اقتباس جديد
                </h5>
                <form action="{{ route('quotes.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="book_id" value="{{ $book->id }}">

                    <div class="mb-3">
                        <label for="quote_text" class="form-label">الاقتباس <span class="text-danger">*</span></label>
                        <textarea name="text" id="quote_text" class="form-control @error('text') is-invalid @enderror" 
                                rows="4" placeholder="اكتب الاقتباس هنا..." required>{{ old('text') }}</textarea>
                        <div class="form-text">شارك اقتباسك المفضل من الكتاب</div>
                        @error('text')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-plus me-2"></i>إضافة الاقتباس
                    </button>
                </form>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            يمكنك إضافة اقتباسات بعد قراءة الكتاب أو تقييمه.
        </div>
    @endif
@else
    <div class="alert alert-info text-center">
        <i class="fas fa-user-lock me-2"></i>
        يرجى <a href="{{ route('login2.page') }}" class="alert-link">تسجيل الدخول</a> لإضافة اقتباسات ومشاهدة اقتباسات القراء.
    </div>
@endauth
</div>
                <!-- Review -->
                <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
                    <!-- Rating Summary -->
                    @if($book->reviews->count() > 0)
                    <div class="rating-summary mb-4 p-3 bg-light rounded">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <h2 class="mb-0">{{ number_format($book->average_rating, 1) }}</h2>
                                <div class="stars mb-2">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="{{ $i <= round($book->average_rating) ? 'fas' : 'far' }} fa-star text-warning"></i>
                                    @endfor
                                </div>
                                <p class="text-muted mb-0">{{ $book->reviews_count }} تقييم</p>
                            </div>
                            <div class="col-md-8">
                                @for ($star = 5; $star >= 1; $star--)
                                    @php
                                        $count = $book->reviews->where('rating', $star)->count();
                                        $percentage = $book->reviews_count > 0 ? ($count / $book->reviews_count) * 100 : 0;
                                    @endphp
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="me-2">{{ $star }} نجوم</span>
                                        <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                            <div class="progress-bar bg-warning" role="progressbar" 
                                                style="width: {{ $percentage }}%"></div>
                                        </div>
                                        <span class="text-muted">{{ $count }}</span>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Success/Error Messages -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- User Reviews -->
                    <div class="user-reviews mb-4">
                        @if($book->reviews->count() > 0)
                            @foreach ($book->reviews->sortByDesc('created_at') as $review)
                            <div class="review-item mb-4 p-3 border rounded">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="avatar me-3">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                            style="width: 40px; height: 40px;">
                                            {{ mb_substr($review->user->name ?? 'م', 0, 1, 'UTF-8') }}
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">{{ $review->user->name ?? 'مستخدم' }}</h6>
                                        <div class="stars mb-1">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i class="{{ $i <= $review->rating ? 'fas' : 'far' }} fa-star text-warning"></i>
                                            @endfor
                                        </div>
                                        <small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                                <p class="mb-0">{{ $review->comment }}</p>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center p-4">
                                <i class="fas fa-star text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 text-muted">لا توجد تقييمات بعد</h5>
                                <p class="text-muted">كن أول من يقيم هذا الكتاب</p>
                            </div>
                        @endif
                    </div>

                    <!-- Review Form -->
                    @auth
                    @php
                        $userReview = $book->reviews->where('user_id', auth()->id())->first();
                    @endphp
                    
                    @if(!$userReview)
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-star me-2"></i>أضف تقييمك
                                </h5>
                                <form action="{{ route('reviews.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="book_id" value="{{ $book->id }}">

                                    <div class="mb-3">
                                        <label for="rating" class="form-label">التقييم <span class="text-danger">*</span></label>
                                        <div class="star-rating">
                                            <input type="radio" id="star5" name="rating" value="5" {{ old('rating') == 5 ? 'checked' : '' }}>
                                            <label for="star5" class="bi bi-star-fill"></label>
                                            <input type="radio" id="star4" name="rating" value="4" {{ old('rating') == 4 ? 'checked' : '' }}>
                                            <label for="star4" class="bi bi-star-fill"></label>
                                            <input type="radio" id="star3" name="rating" value="3" {{ old('rating') == 3 ? 'checked' : '' }}>
                                            <label for="star3" class="bi bi-star-fill"></label>
                                            <input type="radio" id="star2" name="rating" value="2" {{ old('rating') == 2 ? 'checked' : '' }}>
                                            <label for="star2" class="bi bi-star-fill"></label>
                                            <input type="radio" id="star1" name="rating" value="1" {{ old('rating') == 1 ? 'checked' : '' }}>
                                            <label for="star1" class="bi bi-star-fill"></label>
                                        </div>
                                        <div class="rating-feedback mt-2">
                                            <span id="rating-text" class="text-muted">اختر عدد النجوم</span>
                                        </div>
                                        @error('rating')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="comment" class="form-label">تعليقك <span class="text-danger">*</span></label>
                                        <textarea name="comment" id="comment" class="form-control @error('comment') is-invalid @enderror" 
                                                rows="4" placeholder="اكتب تقييمك للكتاب..." required>{{ old('comment') }}</textarea>
                                        @error('comment')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="is_read" value="0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="is_read" name="is_read" value="1" {{ old('is_read') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_read">
                                            <i class="fas fa-book-open me-1"></i>
                                            أؤكد أنني قرأت هذا الكتاب
                                        </label>
                                    </div>
                                    <br>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>إرسال التقييم
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            لقد قمت بتقييم هذا الكتاب من قبل بـ {{ $userReview->rating }} نجوم.
                        </div>
                    @endif
                @else
                    <div class="alert alert-info text-center">
                        <i class="fas fa-user-lock me-2"></i>
                        يرجى <a href="{{ route('login2.page') }}" class="alert-link">تسجيل الدخول</a> لإضافة تقييمك.
                    </div>
                @endauth
                </div>
                 
            </div>
        </div>
            </div>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 999;">
        <div id="cartSuccessToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true" >
            <div class="toast-header bg-success text-white">
                <strong class="me-auto">
                    <i class="fas fa-shopping-cart me-2"></i>
                    تمت الإضافة
                </strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                تمت إضافة الكتاب إلى السلة بنجاح
            </div>
        </div>
    </div>
    
<!-- Sample Books Data -->
<div class="related-books">
    <h3>كتب ذات صلة</h3>
    
    @if($relatedBooks && $relatedBooks->count() > 0)
        <div class="carousel-container">
            <div class="carousel-wrapper" id="carouselWrapper1">
                @foreach($relatedBooks as $relatedBook)
                <div class="book-card">
                    <a href="{{ route('moredetail.page', ['id' => $relatedBook->id]) }}">
                        <img src="{{ asset($relatedBook->image) }}" class="card-img-top" alt="{{ $relatedBook->title }}" loading="lazy">
                    </a>
                    
                    <h6>{{ $relatedBook->title }}</h6>
                    <p class="book-author">
                        <i class="fas fa-user-edit me-1"></i>
                        @if($relatedBook->primaryAuthor)
                            {{ $relatedBook->primaryAuthor->name }}
                        @else
                            {{ $relatedBook->author ?? 'مؤلف غير معروف' }}
                        @endif
                    </p>
                    <div class="price-section">
                        <span class="price">{{ $relatedBook->price }} ر.س</span>
                        <button class="add-btn" 
                        data-book-id="{{ $relatedBook->id }}"
                        data-book-title="{{ htmlspecialchars($relatedBook->title, ENT_QUOTES) }}"
                        data-book-price="{{ $relatedBook->price }}"
                        data-book-image="{{ htmlspecialchars($relatedBook->image, ENT_QUOTES) }}"
                        onclick="addCarouselBookToCart(this)">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            <button class="carousel-nav prev" id="prevBtn1" onclick="moveCarousel(-1, 'carousel1')">
                <i class="fas fa-chevron-right"></i>
            </button>
            <button class="carousel-nav next" id="nextBtn1" onclick="moveCarousel(1, 'carousel1')">
                <i class="fas fa-chevron-left"></i>
            </button>
            <br>
            <div class="carousel-indicators" id="indicators1"></div>
        </div>
        @else
        <!-- Stylized empty state message -->
        <div class="empty-carousel-message">
            <div class="empty-state-card text-center p-5 border rounded bg-light">
                <div class="empty-state-icon mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle" 
                         style="width: 80px; height: 80px;">
                        <i class="fas fa-books text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
                <h4 class="text-dark mb-3">لا توجد كتب ذات صلة</h4>
                <p class="text-muted mb-4">
                    عذراً، لا توجد كتب أخرى متاحة في نفس فئة هذا الكتاب حالياً.<br>
                    يمكنك تصفح مجموعتنا الكاملة من الكتب للعثور على المزيد من الخيارات المثيرة.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="{{ route('index.page') }}" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>العودة للرئيسية
                    </a>
                    <a href="#" class="btn btn-outline-primary" onclick="window.history.back();">
                        <i class="fas fa-arrow-right me-2"></i>العودة للخلف
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
</div>    
    
    <script src="{{ asset('js/moredetail.js') }}"></script>
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/carousel.js') }}"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>
    
    <script>
        
    

        // Function for carousel books
        function addCarouselBookToCart(button) {
            const bookId = button.getAttribute('data-book-id');
            const bookTitle = button.getAttribute('data-book-title');
            const bookPrice = button.getAttribute('data-book-price');
            const bookImage = button.getAttribute('data-book-image');
            const quantity = 1; // Carousel books default to quantity 1
            
            console.log("Adding carousel book:", { bookId, bookTitle, bookPrice, bookImage, quantity });
            
            performAddToCart(bookId, bookTitle, bookPrice, bookImage, quantity, button);
        }

        // Common function that performs the actual add to cart operation
        function performAddToCart(bookId, bookTitle, bookPrice, bookImage, quantity, button) {
            fetch(`/add-to-cart/${bookId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    title: bookTitle,
                    price: bookPrice,
                    image: bookImage,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Add success animation
                    if (button) {
                        button.classList.add('add-success');
                        
                        setTimeout(() => {
                            button.classList.remove('add-success');
                            // Show success feedback
                            const originalContent = button.innerHTML;
                            button.innerHTML = '<i class="fas fa-check"></i>';
                            button.style.background = '#28a745';
                            
                            setTimeout(() => {
                                button.innerHTML = originalContent;
                                button.style.background = '';
                            }, 1500);
                        }, 300);
                    }
                    
                    console.log(`تمت إضافة الكتاب: ${bookTitle} (ID: ${bookId}) إلى السلة`);
                    
                    // Update cart count if you have this function
                    if (typeof updateCartCount === 'function') {
                        updateCartCount(data.cartCount);
                    }
                    else{
                        console.log("updateCartCount function is not defined");
                    }
                    
                    // Show success toast
                    showCartToast(`تمت إضافة "${bookTitle}" إلى السلة`);
                    
                    // Update the cart modal if it's open
                    const cartModal = document.getElementById('cartDetailsModal');
                    if(cartModal && cartModal.classList.contains('show')) {
                        if (typeof showCartModal === 'function') {
                            showCartModal();
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showCartToast('حدث خطأ أثناء الإضافة إلى السلة');
            });
        }
    </script>
    <br> 
    <footer>
        @include('footer')
    </footer>
</body>
</html>