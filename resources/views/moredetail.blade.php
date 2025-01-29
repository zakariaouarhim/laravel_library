<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الكتاب</title>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/moredetailstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" integrity="sha384-gXt9imSW0VcJVHezoNQsP+TNrjYXoGcrqBZJpry9zJt8PCQjobwmhMGaDHTASo9N" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
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
                    <button class="btn btn-primary ms-3" id="addToCartButton" aria-label="أضف الكتاب للسلة" onclick="addToCart({{ $book->id }})">أضف إلى السلة</button>
                </div>

                <div class="row g-2">
                    <div class="col-sm-4">
                        <div class="p-2 border rounded">
                            <span class="fw-bold">اللغة:</span> {{ $book->Langue }}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-2 border rounded">
                            <span class="fw-bold">عدد الصفحات:</span> {{ $book->Page_Num }}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-2 border rounded">
                            <span class="fw-bold">دار النشر:</span> {{ $book->Publishing_House }} 
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Book Details Tabs -->
        <div class="mt-5">
            <ul class="nav nav-tabs" id="bookDetailsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab" aria-controls="description" aria-selected="true">الوصف</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="false">تفاصيل إضافية</button>
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
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="cartSuccessToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
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


    
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ asset('js/moredetail.js') }}"></script>
    <script src="{{ asset('js/header.js') }}"></script>
    
    <br> <br>
    <footer>
        @include('footer')
    </footer>
</body>
</html>