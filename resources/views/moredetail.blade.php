<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الكتاب</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
     <link rel="stylesheet" href="{{ asset('css/moredetailstyl.css') }}">
</head>
<body>
    @include('header')
    <div class="container-fluid py-5">
        <div class="row g-0">
            <!-- Book Image Section -->
            <div class="col-md-5 d-flex align-items-center justify-content-center position-relative">
                <img src="{{ asset('images/image.jfif') }}" alt="غلاف الكتاب" class="img-fluid rounded shadow">
                
            </div>

            <!-- Book Information Section -->
            <div class="col-md-7 p-4">
                <h1 class="fw-bold mb-3">رحلة الألف ميل</h1>
                <p class="text-muted">أحمد خالد توفيق</p>

                <div class="d-flex align-items-center mb-3">
                    <span class="fs-4 text-primary fw-bold">29.99 ريال</span>
                    <span class="badge bg-danger ms-3">10% خصم</span>
                </div>

                <div class="mb-4">
                    <span class="badge bg-secondary">روايات</span>
                    <span class="badge bg-secondary">أدب عربي</span>
                </div>

                <p class="mb-4">رحلة استثنائية تمزج بين الواقع والخيال، تستكشف عمق التجربة الإنسانية من خلال سرد مبهر وشخصيات معقدة.</p>

                <div class="d-flex align-items-center mb-4">
                    <div class="input-group" style="max-width: 120px;">
                        <input type="number" class="form-control text-center" value="1" min="1">
                    </div>
                    <button class="btn btn-primary ms-3">أضف إلى السلة</button>
                </div>

                <div class="row g-2">
                    <div class="col-sm-4">
                        <div class="p-2 border rounded">
                            <span class="fw-bold">اللغة:</span> العربية
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-2 border rounded">
                            <span class="fw-bold">عدد الصفحات:</span> 250
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-2 border rounded">
                            <span class="fw-bold">دار النشر:</span> دار الشروق
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Book Details Tabs -->
        <div class="mt-5">
            <ul class="nav nav-tabs" id="bookDetailsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab">الوصف</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">تفاصيل إضافية</button>
                </li>
            </ul>
            <div class="tab-content border rounded-bottom p-3" id="bookDetailsTabsContent">
                <div class="tab-pane fade show active" id="description" role="tabpanel">
                    <p>في رحلة الألف ميل، يقدم الكاتب رؤية عميقة للتحديات الإنسانية، مستكشفًا مواضيع الهوية والانتماء والصراع الداخلي. من خلال شخصيات متعددة الأبعاد، يرسم الكاتب لوحة فنية تعكس تعقيدات الحياة المعاصرة.</p>
                </div>
                <div class="tab-pane fade" id="details" role="tabpanel">
                    <table class="table table-bordered">
                        <tr>
                            <th>ISBN</th>
                            <td>978-602-1234-56-7</td>
                        </tr>
                        <tr>
                            <th>تاريخ النشر</th>
                            <td>يناير 2024</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="product-page.js"></script>
    @include('footer')
</body>
</html>
