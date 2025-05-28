<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الكتاب</title>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/moredetailstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/carouselstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" integrity="sha384-gXt9imSW0VcJVHezoNQsP+TNrjYXoGcrqBZJpry9zJt8PCQjobwmhMGaDHTASo9N" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <header>
        @include('header')
    </header>
    
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
                    <span class="badge bg-secondary">روايات</span>
                    <span class="badge bg-secondary">أدب عربي</span>
                </div>

                <p class="mb-4">{{ $book->description }}</p>

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
                    <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab" aria-controls="description" aria-selected="true">
                        <i class="fas fa-info-circle me-2"></i>الوصف</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="false">
                        <i class="fas fa-list-ul me-2"></i>تفاصيل إضافية</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab" aria-controls="reviews" aria-selected="false">
                        <i class="fas fa-star me-2"></i>التقييمات
                    </button>
                </li>
            </ul>
            <div class="tab-content border rounded-bottom p-3" id="bookDetailsTabsContent">
                <div class="tab-pane fade show active" id="description" role="tabpanel" aria-labelledby="description-tab">
                    <p>{{ $book->description }}</p>
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
                
                 <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
                    <div class="review-summary mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-4 text-center">
                                <div class="average-rating">
                                    <h2 class="display-4 fw-bold">4.8</h2>
                                    <div class="stars mb-2">
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star-half-alt text-warning"></i>
                                    </div>
                                    <p class="text-muted">من 24 تقييم</p>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="rating-bars">
                                    <div class="rating-bar d-flex align-items-center mb-2">
                                        <div class="rating-text me-2">5 <i class="fas fa-star text-warning"></i></div>
                                        <div class="progress flex-grow-1">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="rating-count ms-2">18</div>
                                    </div>
                                    <div class="rating-bar d-flex align-items-center mb-2">
                                        <div class="rating-text me-2">4 <i class="fas fa-star text-warning"></i></div>
                                        <div class="progress flex-grow-1">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 17%" aria-valuenow="17" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="rating-count ms-2">4</div>
                                    </div>
                                    <div class="rating-bar d-flex align-items-center mb-2">
                                        <div class="rating-text me-2">3 <i class="fas fa-star text-warning"></i></div>
                                        <div class="progress flex-grow-1">
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: 8%" aria-valuenow="8" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="rating-count ms-2">2</div>
                                    </div>
                                    <div class="rating-bar d-flex align-items-center mb-2">
                                        <div class="rating-text me-2">2 <i class="fas fa-star text-warning"></i></div>
                                        <div class="progress flex-grow-1">
                                            <div class="progress-bar bg-danger" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="rating-count ms-2">0</div>
                                    </div>
                                    <div class="rating-bar d-flex align-items-center">
                                        <div class="rating-text me-2">1 <i class="fas fa-star text-warning"></i></div>
                                        <div class="progress flex-grow-1">
                                            <div class="progress-bar bg-danger" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="rating-count ms-2">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="user-reviews">
                        <div class="review-item mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar me-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">م</div>
                                </div>
                                <div>
                                    <h5 class="mb-0">محمد أحمد</h5>
                                    <div class="stars">
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                    </div>
                                </div>
                                <div class="ms-auto text-muted">منذ 3 أيام</div>
                            </div>
                            <p>كتاب رائع بكل المقاييس، استمتعت بقراءته كثيراً وأنصح الجميع باقتنائه!</p>
                        </div>
                        <div class="review-item mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar me-3">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">س</div>
                                </div>
                                <div>
                                    <h5 class="mb-0">سارة علي</h5>
                                    <div class="stars">
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="far fa-star text-warning"></i>
                                    </div>
                                </div>
                                <div class="ms-auto text-muted">منذ أسبوع</div>
                            </div>
                            <p>أسلوب المؤلف جميل ومميز، لكن كنت أتمنى لو كان هناك تفاصيل أكثر في بعض الفصول.</p>
                        </div>
                    </div>
                    <div class="add-review-cta text-center mt-4">
                        <button class="btn btn-outline-primary">
                            <i class="fas fa-pencil-alt me-2"></i>أضف تقييمك للكتاب
                        </button>
                    </div>
                </div>
            </div>
        </div>
            </div>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
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
    

    <!--caroussel-->
    <!-- Sample Books Data -->
    <div class="related-books">
        <h3>كتب ذات صلة</h3>
        <div class="carousel-container">
            <div class="carousel-wrapper" id="carouselWrapper">
                <!-- Sample book cards -->
                @foreach($relatedBooks as $relatedBook)
                <div class="book-card">
                    <img src="{{ asset($relatedBook->image) }}" alt="{{ $relatedBook->title }}">
                    <h6>{{ $relatedBook->title }}</h6>
                    <p class="author">{{ $relatedBook->author }}</p>
                    <div class="price-section">
                        <span class="price">{{ $relatedBook->price }} ر.س</span>
                        <!-- Fixed: Added missing closing quote and parenthesis -->
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
            <button class="carousel-nav prev" id="prevBtn" onclick="moveCarousel(-1)">
                <i class="fas fa-chevron-right"></i>
            </button>
            <button class="carousel-nav next" id="nextBtn" onclick="moveCarousel(1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <br>
            <div class="carousel-indicators" id="indicators"></div>
        </div>
    </div>
    
    
    <script src="{{ asset('js/moredetail.js') }}"></script>
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/carousel.js') }}"></script>
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