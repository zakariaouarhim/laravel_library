<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حسابي -  </title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/account.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Tajawal -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    
</head>
<body>
    @include('header')
    
    <main class="container-fluid">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="container">
                <div class="text-center">
                    <img src="{{ session('user_avatar') ? asset('storage/' . session('user_avatar')) : asset('images/author/user_avatar1.jpg') }}" 
                         alt="الصورة الشخصية" class="profile-avatar">
                    <h2>{{ session('user_name', 'المستخدم') }}</h2>
                    <p class="mb-0">{{ session('user_email', 'user@example.com') }}</p>
                    <p class="mb-0">
                        <i class="bi bi-calendar-event me-2"></i>
                        @auth
                            انضم في {{ Auth::user()->created_at->locale('ar')->translatedFormat('F Y') }}
                        @else
                            انضم في {{ session('user_updated_at', 'يناير 2024') }}
                        @endauth
                    </p>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="row">
                <!-- Statistics Cards -->
                <div class="col-md-3 mb-4">
                    <div class="stats-card">
                        <div class="stats-number">{{ $booksRead ?? 0 }}</div>
                        <div class="stats-label">كتاب مقروء</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stats-card">
                        <div class="stats-number">{{ $reviews->count() ?? 0 }}</div>
                        <div class="stats-label">مراجعة</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stats-card">
                        <div class="stats-number">{{ $quotes->count() ?? 0 }}</div>
                        <div class="stats-label">اقتباس</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stats-card">
                        <div class="stats-number">{{ $OrderNumber ?? 0 }}</div>
                        <div class="stats-label">طلبيات</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Main Content -->
                <div class="col-md-8">
                    <!-- Navigation Tabs -->
                    <ul class="nav nav-pills mb-4" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="activity-tab" data-bs-toggle="pill" data-bs-target="#activity" type="button" role="tab">
                                <i class="bi bi-activity me-2"></i>النشاطات
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="reviews-tab" data-bs-toggle="pill" data-bs-target="#reviews" type="button" role="tab">
                                <i class="bi bi-star me-2"></i>المراجعات
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="quotes-tab" data-bs-toggle="pill" data-bs-target="#quotes" type="button" role="tab">
                                <i class="bi bi-quote me-2"></i>الاقتباسات
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="books-tab" data-bs-toggle="pill" data-bs-target="#books" type="button" role="tab">
                                <i class="bi bi-book me-2"></i>كتبي
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Activity Tab -->
                        <div class="tab-pane fade show active" id="activity" role="tabpanel">
                            <div class="profile-card">
                                <h5 class="mb-3">
                                    <i class="bi bi-activity me-2"></i>
                                    النشاطات الأخيرة
                                </h5>

                                {{-- Last Review --}}
                                @if(isset($lastReview))
                                    <h6 class="mb-3">
                                        <i class="bi bi-star me-2"></i>
                                        اخر مراجعة :
                                    </h6>
                                    <div class="activity-item">
                                        @if($lastReview->book && $lastReview->book->image)
                                            <img src="{{ asset($lastReview->book->image) }}" 
                                                alt="{{ $lastReview->book->title }}" 
                                                class="book-thumb me-3">
                                        @endif
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">{{ $lastReview->book->title ?? 'عنوان غير متوفر' }}</div>
                                            <div class="text-muted small">{{ $lastReview->comment }}</div>
                                            <div class="text-muted small">{{ $lastReview->updated_at->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                    <br>
                                @endif

                                {{-- Last Wishlist --}}
                                @if($lastWishlistBook)
                                    <h6 class="mb-3">
                                        <i class="bi bi-heart add-icon "></i>
                                        اخر المتمنيات :
                                    </h6>
                                    <div class="activity-item">
                                        @if($lastWishlistBook->image)
                                            <img src="{{ asset($lastWishlistBook->image) }}" 
                                                alt="{{ $lastWishlistBook->title }}" 
                                                class="book-thumb me-3">
                                        @endif
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">{{ $lastWishlistBook->title }}</div>
                                            <div class="text-muted small">تمت إضافته إلى المفضلة منذ {{ $lastWishlistBook->pivot->created_at->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                @endif
                                {{-- Last quote --}}
                                @if($lastQuote)
                                <br>
                                    <h6 class="mb-3">
                                        <i class="bi bi-quote me-2"></i>
                                        اخر اقتباس :
                                    </h6>
                                    <div class="activity-item">
                                        
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">"{{ $lastQuote->text }}"</div>
                                            <div class="text-muted small">تمت إضافته إلى الاقتباسات منذ {{ $lastQuote->created_at->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                @endif

                                {{-- No activity fallback --}}
                                @if(!isset($lastReview) && !$lastWishlistBook && !$lastQuote)
                                    <div class="empty-state">
                                        <i class="bi bi-activity"></i>
                                        <h6>لا يوجد نشاطات بعد</h6>
                                        <p>ابدأ بقراءة كتاب أو كتابة مراجعة لتظهر نشاطاتك هنا</p>
                                    </div>
                                @endif
                            </div>
                        </div>


                        <!-- Reviews Tab -->
                        <div class="tab-pane fade" id="reviews" role="tabpanel">
                            <div class="profile-card">
                                <h5 class="mb-3">
                                    <i class="bi bi-star me-2"></i>
                                    أحدث المراجعات
                                </h5>
                                
                                @if(isset($reviews) && count($reviews) > 0)
                                   @foreach($reviews as $review)
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="bi bi-star-fill"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-2">
                                            @if($review->book && $review->book->image)
                                                <img src="{{ asset($review->book->image) }}" 
                                                    alt="{{ $review->book->title }}" 
                                                    class="book-thumb me-3">
                                            @endif
                                            <div>
                                                <div class="fw-bold">{{ $review->book->title ?? 'عنوان غير متوفر' }}</div>
                                                <div class="text-warning mb-1">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>
                                                    @endfor
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-muted">{{ $review->comment }}</div>
                                        <div class="text-muted small">{{ $review->updated_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                                @endforeach
                                @else
                                    <div class="empty-state">
                                        <i class="bi bi-star"></i>
                                        <h6>لا توجد مراجعات بعد</h6>
                                        <p>اكتب مراجعتك الأولى لكتاب قرأته</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Quotes Tab -->
                        <div class="tab-pane fade" id="quotes" role="tabpanel">
                            <div class="profile-card">
                                <h5 class="mb-3">
                                    <i class="bi bi-quote me-2"></i>
                                    الاقتباسات المفضلة
                                </h5>
                                
                                @if(isset($quotes) && count($quotes) > 0)
                                    @foreach($quotes as $quote)
                                    <div class="activity-item">
                                        <div class="activity-icon feed-icon" >
                                            <i class="bi bi-quote"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">{{ $quote->book->title}}</div>
                                            <div class="fst-italic mb-2">"{{ $quote['text'] }}"</div>
                                            <div class="text-muted small">{{ $quote['date'] }}</div>
                                        </div>
                                    </div>
                                    @endforeach
                                @else
                                    <div class="empty-state">
                                        <i class="bi bi-quote"></i>
                                        <h6>لا توجد اقتباسات بعد</h6>
                                        <p>احفظ اقتباساتك المفضلة من الكتب التي تقرأها</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Books Tab -->
                        <div class="tab-pane fade" id="books" role="tabpanel">
                            <div class="profile-card">
                                <h5 class="mb-3">
                                    <i class="bi bi-book me-2"></i>
                                    كتبي
                                </h5>
                                
                                @if(isset($WishlistBook) && $WishlistBook->isNotEmpty())
                                    <div class="row">
                                        @foreach($WishlistBook as $book)
                                            <div class="col-md-6 mb-4">
                                                <div class="book-card d-flex align-items-start">
                                                    <img src="{{ asset($book['image']) }}" 
                                                        alt="{{ $book['title'] ?? 'صورة كتاب' }}" 
                                                        loading="lazy" 
                                                        class="book-thumb me-3">
                                                    <div class="p-2 flex-fill">
                                                        <h6 class="fw-bold mb-1">{{ $book['title'] }}</h6>
                                                        <p class="text-muted small mb-2">{{ $book['author'] ?? 'مؤلف غير معروف' }}</p>

                                                        <small class="text-muted">التقدم في القراءة</small>
                                                        <div class="reading-progress">
                                                            <div class="reading-progress-bar" style="width: {{ $book['progress'] ?? 0 }}%"></div>
                                                        </div>
                                                        <small class="text-muted">{{ $book['progress'] ?? 0 }}%</small>

                                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                                            <span class="badge bg-{{ $book['status_color'] ?? 'secondary' }}">{{ $book['status'] ?? 'غير محدد' }}</span>
                                                            <a href="{{ route('moredetail.page', ['id' => $book->id]) }}" class="btn btn-sm btn-outline-primary">عرض التفاصيل</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="empty-state">
                                        <i class="bi bi-book" style="font-size: 2rem;"></i>
                                        <h6 class="mt-2">لا توجد كتب بعد</h6>
                                        <p class="text-muted">ابدأ بإضافة كتب إلى مكتبتك الشخصية</p>
                                        <a href="{{ route('index.page') }}" class="btn btn-primary">
                                            <i class="bi bi-plus-circle me-2"></i>
                                            تصفح الكتب
                                        </a>
                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Reading Goals -->
                    <div class="profile-card">
                        <h6 class="mb-3">
                            <i class="bi bi-target me-2"></i>
                            هدف القراءة لهذا العام
                        </h6>
                        
                        <div class="text-center">
                            <div class="mb-3">
                                <div class="display-6 fw-bold text-primary">
                                    {{ $booksRead }}/<span id="goalTarget">{{ $target }}</span>
                                </div>
                                <small class="text-muted">كتاب</small>
                            </div>
                            
                            <div class="reading-progress mb-3">
                                <div class="reading-progress-bar" style="width: {{ $progressPercent }}%"></div>
                            </div>
                            
                            <small class="text-muted">{{ $progressPercent }}% مكتمل</small>
                            
                            <!-- Goal Setting/Editing Controls -->
                            <div class="mt-3">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editGoalModal">
                                    <i class="bi bi-pencil-square me-1"></i>
                                    تعديل الهدف
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Goal Modal -->
                    <div class="modal fade" id="editGoalModal" tabindex="-1" aria-labelledby="editGoalModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editGoalModalLabel">
                                        <i class="bi bi-target me-2"></i>
                                        تحديد هدف القراءة
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                
                                <form id="goalForm" method="POST" action="{{ route('ReadingGoal') }}">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="readingTarget" class="form-label">عدد الكتب المراد قراءتها هذا العام</label>
                                            <input type="number" class="form-control" id="readingTarget" name="target" 
                                                value="{{ $target }}" min="1" max="365" required>
                                            <div class="form-text">اختر عدد الكتب التي تريد قراءتها (من 1 إلى 365 كتاب)</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="card bg-light">
                                                <div class="card-body text-center">
                                                    <h6 class="card-title">معاينة الهدف</h6>
                                                    <div class="display-6 fw-bold text-primary mb-2">
                                                        {{ $booksRead }}/<span id="previewTarget">{{ $target }}</span>
                                                    </div>
                                                    <div class="reading-progress mb-2">
                                                        <div class="reading-progress-bar" id="previewProgressBar" 
                                                            style="width: {{ $progressPercent }}%"></div>
                                                    </div>
                                                    <small class="text-muted">
                                                        <span id="previewPercent">{{ $progressPercent }}</span>% مكتمل
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        @if($booksRead > 0)
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            لقد قرأت {{ $booksRead }} كتاب حتى الآن هذا العام
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-1"></i>
                                            حفظ الهدف
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Recommendations -->
                    <div class="profile-card">
                    <h6 class="mb-3">
                        <i class="bi bi-lightbulb me-2"></i>
                        ترشيحات لك
                        @if ( count($reviews) > 0 )
                            @if(isset($recommendations) && count($recommendations) > 0)
                            <small class="text-muted">({{ count($recommendations) }} كتاب)</small>
                            @endif
                        @endif
                        
                    </h6>
                    @if ( count($reviews) > 0 )
                    @if(isset($recommendations) && count($recommendations) > 0)
                        @foreach($recommendations as $rec)
                        <div class="recommendation-item d-flex mb-3 p-2 rounded" style="background: #f8f9fa;">
                            
                            <img src="{{ $rec['image'] }}" 
                                alt="{{ $rec['title'] }}" 
                                class="rounded me-3" 
                                style="width: 60px; height: 80px; object-fit: cover; cursor: pointer;"
                                onclick="'#'">
                            
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <a href="#" 
                                    class="text-decoration-none text-dark">
                                        {{ $rec['title'] }}
                                    </a>
                                </h6>
                                
                                <p class="text-muted small mb-1">
                                    <i class="bi bi-person me-1"></i>{{ $rec['author'] }}
                                </p>
                                
                                @if(isset($rec['category']) && $rec['category'])
                                    <p class="text-muted small mb-1">
                                        <i class="bi bi-tag me-1"></i>{{ $rec['category'] }}
                                    </p>
                                @endif
                                
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="text-warning small">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="bi bi-star{{ $i <= $rec['rating'] ? '-fill' : '' }}"></i>
                                        @endfor
                                        <span class="text-muted ms-1">({{ $rec['rating'] }}/5)</span>
                                    </div>
                                    
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary btn-sm toggle-wishlist-btn" 
                                                data-book-id="{{ $rec['id'] }}"
                                                title="أضف للمفضلة">
                                            {{-- Empty Heart (Add) --}}
                                            <i class="bi bi-heart add-icon {{ in_array($rec['id'], $wishlistBookIds) ? 'd-none' : '' }}"></i>

                                            {{-- Filled Heart (Remove) --}}
                                            <i class="bi bi-heart-fill remove-icon text-danger {{ in_array($rec['id'], $wishlistBookIds) ? '' : 'd-none' }}"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary btn-sm"
                                                onclick="hideRecommendation({{ $rec['id'] }})"
                                                title="إخفاء هذا الترشيح">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </div>
                        @endforeach
                        
                        <div class="text-center mt-3">
                            <a href="#" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-arrow-left me-1"></i>
                                عرض المزيد من الترشيحات
                            </a>
                        </div>
                    @endif   
                    @else
                        <div class="empty-state text-center py-4">
                            <i class="bi bi-lightbulb" style="font-size: 2rem; color: #6c757d;"></i>
                            <h6 class="mt-2">لا توجد ترشيحات بعد</h6>
                            <p class="text-muted">اقرأ المزيد من الكتب للحصول على ترشيحات شخصية</p>
                            <a href="{{ route('index.page') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-book me-1"></i>
                                تصفح الكتب
                            </a>
                        </div>
                    @endif
                </div>

                    <!-- Followers -->
                    <div class="profile-card">
                        <h6 class="mb-3">
                            <i class="bi bi-people me-2"></i>
                            المتابعون
                        </h6>
                        @if(isset($followersData) && count($followersData) > 0)
                            <div class="row">
                                @foreach($followersData as $follower)
                                <div class="col-4 text-center mb-3">
                                    <img src="{{ $follower['avatar'] }}" alt="{{ $follower['name'] }}" class="rounded-circle mb-2" style="width: 50px; height: 50px; object-fit: cover;">
                                    <div class="small fw-bold">{{ $follower['name'] }}</div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                <h6>لا يوجد متابعون بعد</h6>
                                <p>شارك مراجعاتك واقتباساتك لجذب المتابعين</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    @include('footer')
    
    <!-- Scripts -->
    
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/account.js') }}"></script>
    
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>