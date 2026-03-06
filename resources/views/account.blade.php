<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>حسابي - مكتبة الفقراء</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
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
                    <div class="avatar-wrapper" onclick="document.getElementById('avatarInput').click()">
                        <img src="{{ Auth::user()->avatar ? asset('storage/' . Auth::user()->avatar) : asset('images/author/user_avatar1.jpg') }}"
                             alt="الصورة الشخصية" class="profile-avatar" id="avatarPreview" width="100" height="100">
                        <div class="avatar-overlay">
                            <i class="bi bi-camera"></i>
                            <span>تغيير الصورة</span>
                        </div>
                    </div>
                    <form id="avatarForm" action="{{ route('avatar.upload') }}" method="POST" enctype="multipart/form-data" style="display:none;">
                        @csrf
                        <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/webp" onchange="document.getElementById('avatarForm').submit();">
                    </form>
                    @if($errors->has('avatar'))
                        <div class="text-warning mt-2" style="font-size:0.85rem;">{{ $errors->first('avatar') }}</div>
                    @endif
                    <h2>{{ Auth::user()->name }}</h2>
                    <p class="mb-0">{{ Auth::user()->email }}</p>
                    <p class="mb-0">
                        <i class="bi bi-calendar-event me-2"></i>
                        انضم في {{ Auth::user()->created_at->locale('ar')->translatedFormat('F Y') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="row">
                <!-- Statistics Cards -->
                <div class="col-md-3 col-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-icon"><i class="bi bi-book-fill"></i></div>
                        <div class="stats-number">{{ $booksRead ?? 0 }}</div>
                        <div class="stats-label">كتاب مقروء</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-icon"><i class="bi bi-star-fill"></i></div>
                        <div class="stats-number">{{ $reviewCount }}</div>
                        <div class="stats-label">مراجعة</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-icon"><i class="bi bi-quote"></i></div>
                        <div class="stats-number">{{ $quoteCount }}</div>
                        <div class="stats-label">اقتباس</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <a href="{{ route('my-orders.index') }}" class="text-decoration-none">
                        <div class="stats-card">
                            <div class="stats-icon"><i class="bi bi-bag-fill"></i></div>
                            <div class="stats-number">{{ $OrderNumber ?? 0 }}</div>
                            <div class="stats-label">طلبيات</div>
                        </div>
                    </a>
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
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="shelves-tab" data-bs-toggle="pill" data-bs-target="#shelves" type="button" role="tab">
                                <i class="bi bi-bookshelf me-2"></i>رف القراءة
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
                                                class="book-thumb me-3" width="50" height="70" loading="lazy">
                                        @endif
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">{{ $lastReview->book->title ?? 'عنوان غير متوفر' }}</div>
                                            <div class="text-muted small">{{ $lastReview->comment }}</div>
                                            <div class="text-muted small">{{ $lastReview->updated_at->diffForHumans() }}</div>
                                        </div>
                                    </div>
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
                                                class="book-thumb me-3" width="50" height="70" loading="lazy">
                                        @endif
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">{{ $lastWishlistBook->title }}</div>
                                            <div class="text-muted small">تمت إضافته إلى المفضلة منذ {{ $lastWishlistBook->pivot->created_at->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                @endif
                                {{-- Last quote --}}
                                @if($lastQuote)
                                    <h6 class="mb-3 mt-3">
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
                                
                                @if($reviewCount > 0)
                                    <div id="reviewsList">
                                    @foreach($reviews as $index => $review)
                                        <div class="activity-item review-item" @if($index >= 5) style="display:none;" @endif>
                                            <div class="activity-icon">
                                                <i class="bi bi-star-fill"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    @if($review->book && $review->book->image)
                                                        <img src="{{ asset($review->book->image) }}"
                                                            alt="{{ $review->book->title }}"
                                                            class="book-thumb me-3" width="50" height="70" loading="lazy">
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
                                    </div>
                                    @if($reviewCount > 5)
                                        <div class="text-center mt-3">
                                            <button class="btn btn-outline-primary btn-sm" id="showMoreReviews" onclick="showMore('reviewsList', 'review-item', this)">
                                                <i class="bi bi-chevron-down me-1"></i>
                                                عرض المزيد ({{ $reviewCount - 5 }})
                                            </button>
                                        </div>
                                    @endif
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
                                
                                @if($quoteCount > 0)
                                    <div id="quotesList">
                                    @foreach($quotes as $index => $quote)
                                        <div class="activity-item quote-item" @if($index >= 5) style="display:none;" @endif>
                                            <div class="activity-icon feed-icon">
                                                <i class="bi bi-quote"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold">{{ $quote->book->title }}</div>
                                                <div class="fst-italic mb-2">"{{ $quote['text'] }}"</div>
                                                <div class="text-muted small">{{ $quote['date'] }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                    </div>
                                    @if($quoteCount > 5)
                                        <div class="text-center mt-3">
                                            <button class="btn btn-outline-primary btn-sm" onclick="showMore('quotesList', 'quote-item', this)">
                                                <i class="bi bi-chevron-down me-1"></i>
                                                عرض المزيد ({{ $quoteCount - 5 }})
                                            </button>
                                        </div>
                                    @endif
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
                                                    <img src="{{ asset($book['image'] ?? 'images/book-placeholder.png') }}"
                                                        alt="{{ $book['title'] ?? 'صورة كتاب' }}"
                                                        loading="lazy" width="50" height="70"
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
                                                            <a href="{{ route('moredetail2.page', ['id' => $book->id]) }}" class="btn btn-sm btn-outline-primary">عرض التفاصيل</a>
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

                        <!-- Reading Shelves Tab -->
                        <div class="tab-pane fade" id="shelves" role="tabpanel">
                            <div class="profile-card">
                                <h5 class="mb-3">
                                    <i class="bi bi-bookshelf me-2"></i>
                                    رف القراءة
                                </h5>

                                @php
                                    $shelfBooks = \App\Models\ReadingShelf::where('user_id', auth()->id())
                                        ->with('book.primaryAuthor')
                                        ->get()
                                        ->groupBy('status');
                                    $shelfSections = [
                                        'reading'      => ['label' => 'أقرأ حالياً', 'icon' => 'bi-glasses', 'color' => 'primary'],
                                        'want_to_read' => ['label' => 'أريد قراءته', 'icon' => 'bi-bookmark', 'color' => 'warning'],
                                        'read'         => ['label' => 'قرأته', 'icon' => 'bi-check-circle', 'color' => 'success'],
                                    ];
                                @endphp

                                @php $hasAnyShelf = false; @endphp
                                @foreach($shelfSections as $status => $section)
                                    @php $items = $shelfBooks->get($status, collect()); @endphp
                                    @if($items->isNotEmpty())
                                        @php $hasAnyShelf = true; @endphp
                                        <h6 class="mt-4 mb-3">
                                            <i class="bi {{ $section['icon'] }} me-2 text-{{ $section['color'] }}"></i>
                                            {{ $section['label'] }}
                                            <span class="badge bg-{{ $section['color'] }} ms-1">{{ $items->count() }}</span>
                                        </h6>
                                        <div class="row">
                                            @foreach($items as $entry)
                                                <div class="col-md-6 mb-3">
                                                    <div class="book-card d-flex align-items-start">
                                                        <img src="{{ asset($entry->book->image ?? 'images/book-placeholder.png') }}"
                                                             alt="{{ $entry->book->title }}"
                                                             loading="lazy" width="50" height="70"
                                                             class="book-thumb me-3">
                                                        <div class="p-2 flex-fill">
                                                            <h6 class="fw-bold mb-1">{{ Str::limit($entry->book->title, 40) }}</h6>
                                                            <p class="text-muted small mb-1">{{ $entry->book->author ?? 'مؤلف غير معروف' }}</p>
                                                            @if($entry->started_at)
                                                                <small class="text-muted"><i class="bi bi-calendar3"></i> بدأت: {{ $entry->started_at->format('d/m/Y') }}</small>
                                                            @endif
                                                            @if($entry->finished_at)
                                                                <small class="text-success d-block"><i class="bi bi-check2"></i> أنهيت: {{ $entry->finished_at->format('d/m/Y') }}</small>
                                                            @endif
                                                            <div class="mt-2">
                                                                <a href="{{ route('moredetail2.page', $entry->book->id) }}" class="btn btn-sm btn-outline-primary">عرض</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach

                                @if(!$hasAnyShelf)
                                    <div class="empty-state">
                                        <i class="bi bi-bookshelf" style="font-size: 2rem;"></i>
                                        <h6 class="mt-2">رف القراءة فارغ</h6>
                                        <p class="text-muted">أضف كتباً من صفحة تفاصيل الكتاب لتتبع قراءتك</p>
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
                        @if(isset($recommendations) && count($recommendations) > 0)
                            <small class="text-muted">({{ count($recommendations) }} كتاب)</small>
                        @endif
                    </h6>
                    @if(isset($recommendations) && count($recommendations) > 0)
                        @foreach($recommendations as $rec)
                        <div class="recommendation-item d-flex mb-3 p-2 rounded">
                            
                            <img src="{{ $rec['image'] }}"
                                alt="{{ $rec['title'] }}"
                                class="rounded me-3 rec-thumb" width="60" height="85" loading="lazy"
                                onclick="window.location.href='{{ route('moredetail2.page', ['id' => $rec['id']]) }}'">
                            
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <a href="{{ route('moredetail2.page', ['id' => $rec['id']]) }}"
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
                            <a href="{{ route('recommendations.index') }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-arrow-left me-1"></i>
                                عرض المزيد من الترشيحات
                            </a>
                        </div>
                    @else
                        <div class="empty-state text-center py-4">
                            <i class="bi bi-lightbulb" style="font-size: 2rem; color: #6c757d;"></i>
                            <h6 class="mt-2">لا توجد ترشيحات بعد</h6>
                            <p class="text-muted">تابع مؤلفيك المفضلين أو أضف كتباً لقائمة الأمنيات للحصول على ترشيحات</p>
                            <a href="{{ route('index.page') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-book me-1"></i>
                                تصفح الكتب
                            </a>
                        </div>
                    @endif
                </div>

                    <!-- Who I Follow -->
                    <div class="profile-card" id="followingCard">
                        <h6 class="mb-3">
                            <i class="bi bi-person-plus"></i>
                            متابعاتي
                            <small class="text-muted fw-normal">
                                ({{ $followedAuthors->count() + $followedPublishers->count() }})
                            </small>
                        </h6>

                        @if($followedAuthors->isEmpty() && $followedPublishers->isEmpty())
                            <div class="empty-state">
                                <i class="bi bi-bookmark-star"></i>
                                <h6 class="mt-2">لا تتابع أحداً بعد</h6>
                                <p>تابع مؤلفيك المفضلين ودور النشر لتصلك إشعارات عند إضافة كتب جديدة</p>
                                <div class="d-flex gap-2 justify-content-center flex-wrap mt-2">
                                    <a href="{{ route('authors.index') }}" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-pen-fancy me-1"></i> تصفح المؤلفين
                                    </a>
                                    <a href="{{ route('publishers.index') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-building me-1"></i> دور النشر
                                    </a>
                                </div>
                            </div>
                        @else
                            {{-- Authors --}}
                            @if($followedAuthors->isNotEmpty())
                                <p class="text-muted small mb-2 fw-semibold">
                                    <i class="bi bi-pen-fancy me-1"></i> المؤلفون
                                </p>
                                @foreach($followedAuthors as $author)
                                <div class="d-flex align-items-center mb-2 p-2 rounded follow-item" data-id="{{ $author->id }}" data-type="author">
                                    @if($author->profile_image)
                                        <img src="{{ asset('storage/' . $author->profile_image) }}"
                                             alt="{{ $author->name }}"
                                             class="rounded-circle me-2 follow-avatar" width="36" height="36" loading="lazy">
                                    @else
                                        <div class="rounded-circle me-2 d-flex align-items-center justify-content-center follow-avatar-placeholder">
                                            {{ mb_substr($author->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <div class="flex-grow-1 follow-name">
                                        <a href="{{ route('author.show', $author->id) }}"
                                           class="fw-semibold text-decoration-none text-dark small d-block text-truncate">
                                            {{ $author->name }}
                                        </a>
                                        @if($author->nationality)
                                            <div class="text-muted follow-meta">{{ $author->nationality }}</div>
                                        @endif
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger ms-2"
                                            onclick="unfollowItem('author', {{ $author->id }}, this)"
                                            title="إلغاء المتابعة">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                @endforeach
                            @endif

                            {{-- Publishers --}}
                            @if($followedPublishers->isNotEmpty())
                                <p class="text-muted small mb-2 fw-semibold {{ $followedAuthors->isNotEmpty() ? 'mt-3' : '' }}">
                                    <i class="bi bi-building me-1"></i> دور النشر
                                </p>
                                @foreach($followedPublishers as $publisher)
                                <div class="d-flex align-items-center mb-2 p-2 rounded follow-item" data-id="{{ $publisher->id }}" data-type="publisher">
                                    @if($publisher->logo)
                                        <img src="{{ asset('storage/' . $publisher->logo) }}"
                                             alt="{{ $publisher->name }}"
                                             class="rounded me-2 follow-avatar" style="object-fit:contain;" width="36" height="36" loading="lazy">
                                    @else
                                        <div class="rounded me-2 d-flex align-items-center justify-content-center follow-avatar-placeholder">
                                            <i class="bi bi-building"></i>
                                        </div>
                                    @endif
                                    <div class="flex-grow-1 follow-name">
                                        <a href="{{ route('publisher.show', $publisher->id) }}"
                                           class="fw-semibold text-decoration-none text-dark small d-block text-truncate">
                                            {{ $publisher->name }}
                                        </a>
                                        @if($publisher->country)
                                            <div class="text-muted follow-meta">{{ $publisher->country }}</div>
                                        @endif
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger ms-2"
                                            onclick="unfollowItem('publisher', {{ $publisher->id }}, this)"
                                            title="إلغاء المتابعة">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                @endforeach
                            @endif
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

        function unfollowItem(type, id, btn) {
            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
            fetch('/follow/' + type + '/' + id, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && !data.following) {
                    // Fade out and remove the row
                    var row = btn.closest('.follow-item');
                    row.style.transition = 'opacity 0.3s ease';
                    row.style.opacity = '0';

                    setTimeout(function() {
                        row.remove();

                        // Update the counter
                        var card = document.getElementById('followingCard');
                        var counter = card.querySelector('.text-muted.fw-normal');
                        var remaining = card.querySelectorAll('.follow-item').length;
                        counter.textContent = '(' + remaining + ')';

                        // Show empty state if nothing left
                        if (remaining === 0) {
                            location.reload();
                        }
                    }, 300);

                    showToast('تم إلغاء المتابعة', 'success');
                } else {
                    btn.innerHTML = '<i class="bi bi-x-lg"></i>';
                    btn.disabled = false;
                }
            })
            .catch(function() {
                btn.innerHTML = '<i class="bi bi-x-lg"></i>';
                btn.disabled = false;
                showToast('حدث خطأ، يرجى المحاولة لاحقاً', 'error');
            });
        }

        function showMore(listId, itemClass, btn) {
            var items = document.getElementById(listId).querySelectorAll('.' + itemClass);
            items.forEach(function(item) {
                item.style.display = '';
            });
            btn.remove();
        }
    </script>
</body>
</html>