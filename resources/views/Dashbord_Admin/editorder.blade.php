<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تعديل الطلب</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    
    <style>
        * {
            font-family: 'Cairo', sans-serif;
        }

        body {
            background-color: #f5f7fa;
        }

        .main-content {
            padding: 2rem 1rem;
        }

        .page-header {
            margin-bottom: 2rem;
            border-bottom: 3px solid #3498db;
            padding-bottom: 1rem;
        }

        .page-header h2 {
            color: #2c3e50;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .page-header i {
            color: #3498db;
            font-size: 2rem;
        }

        .form-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .form-section h4 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #3498db;
        }

        .form-section h4 i {
            color: #3498db;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 1px solid #e0e6ed;
            padding: 0.8rem;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .form-control:disabled,
        .form-select:disabled {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        .info-box {
            background: #f8f9fa;
            padding: 1.2rem;
            border-radius: 10px;
            border-right: 4px solid #3498db;
            margin-bottom: 1.5rem;
        }

        .info-box-title {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .info-box-value {
            color: #2c3e50;
            font-weight: 500;
            font-size: 1rem;
        }

        .books-list {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .book-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.8rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-right: 3px solid #3498db;
        }

        .book-item:last-child {
            margin-bottom: 0;
        }

        .book-info {
            flex: 1;
        }

        .book-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.3rem;
        }

        .book-quantity {
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .book-price {
            font-weight: 600;
            color: #3498db;
            font-size: 1.1rem;
            text-align: left;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-custom {
            padding: 0.8rem 2rem;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-save {
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(39, 174, 96, 0.3);
            color: white;
        }

        .btn-cancel {
            background: #bdc3c7;
            color: white;
        }

        .btn-cancel:hover {
            background: #95a5a6;
            transform: translateY(-2px);
            color: white;
        }

        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 2rem;
        }

        .total-box {
            background: linear-gradient(135deg, #3498db, #2196F3);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            margin-top: 1rem;
        }

        .total-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .total-value {
            font-size: 2rem;
            font-weight: 700;
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }

            .btn-custom {
                width: 100%;
                justify-content: center;
            }

            .book-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .book-price {
                text-align: right;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    @include('Dashbord_Admin.dashbordHeader')
    
    <div class="container-fluid">
        <div class="row">
            @include('Dashbord_Admin.Sidebar')

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <h2>
                        <i class="fas fa-edit"></i>
                        تعديل الطلب #{{ $order->id }}
                    </h2>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.orders.update', $order->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Order Info Section -->
                    <div class="form-section">
                        <h4>
                            <i class="fas fa-info-circle"></i>
                            معلومات الطلب
                        </h4>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box">
                                    <div class="info-box-title">رقم الطلب</div>
                                    <div class="info-box-value">#{{ $order->id }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box">
                                    <div class="info-box-title">رقم التتبع</div>
                                    <div class="info-box-value">{{ $order->tracking_number ?? '-' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box">
                                    <div class="info-box-title">تاريخ الطلب</div>
                                    <div class="info-box-value">{{ $order->created_at->format('d-m-Y H:i') }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box">
                                    <div class="info-box-title">المبلغ الإجمالي</div>
                                    <div class="info-box-value">{{ number_format($order->total_price, 2) }} ر.س</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">حالة الطلب</label>
                            <select name="status" class="form-select" required>
                                <option value="">اختر حالة</option>
                                <option value="pending" @if($order->status == 'pending') selected @endif>قيد الانتظار</option>
                                <option value="processing" @if($order->status == 'processing') selected @endif>قيد المعالجة</option>
                                <option value="shipped" @if($order->status == 'shipped') selected @endif>مشحون</option>
                                <option value="delivered" @if($order->status == 'delivered') selected @endif>تم التسليم</option>
                                <option value="cancelled" @if($order->status == 'cancelled') selected @endif>ملغى</option>
                                <option value="Failed" @if($order->status == 'Failed') selected @endif>فشل</option>
                                <option value="Refunded" @if($order->status == 'Refunded') selected @endif>مسترجع</option>
                                <option value="returned" @if($order->status == 'returned') selected @endif>مرتجع</option>
                            </select>
                        </div>
                    </div>

                    <!-- Shipping Info Section -->
                    <div class="form-section">
                        <h4>
                            <i class="fas fa-truck"></i>
                            معلومات الشحن
                        </h4>

                        <div class="form-group">
                            <label class="form-label">عنوان الشحن</label>
                            <textarea 
                                name="shipping_address" 
                                class="form-control" 
                                rows="3"
                                placeholder="أدخل عنوان الشحن">{{ $order->shipping_address }}</textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">عنوان الفاتورة</label>
                            <textarea 
                                name="billing_address" 
                                class="form-control" 
                                rows="3"
                                placeholder="أدخل عنوان الفاتورة">{{ $order->billing_address }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box">
                                    <div class="info-box-title">طريقة الدفع</div>
                                    <div class="info-box-value">
                                        @if($order->payment_method === 'cod')
                                            الدفع عند الاستلام
                                        @else
                                            بطاقة ائتمان
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box">
                                    <div class="info-box-title">المستخدم</div>
                                    <div class="info-box-value">{{ $order->user_id ?? 'زائر' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Books Section -->
                    @if($order->orderDetails->count() > 0)
                    <div class="form-section">
                        <h4>
                            <i class="fas fa-books"></i>
                            الكتب المطلوبة
                        </h4>

                        <div class="books-list">
                            @foreach($order->orderDetails as $item)
                            <div class="book-item">
                                <div class="book-info">
                                    <div class="book-title">{{ $item->book->title }}</div>
                                    <div class="book-quantity">الكمية: {{ $item->quantity }}</div>
                                </div>
                                <div class="book-price">{{ number_format($item->price * $item->quantity, 2) }} ر.س</div>
                            </div>
                            @endforeach
                        </div>

                        <div class="total-box">
                            <div class="total-label">المجموع الكلي</div>
                            <div class="total-value">{{ number_format($order->total_price, 2) }} ر.س</div>
                        </div>
                    </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button type="submit" class="btn-custom btn-save">
                            <i class="fas fa-save"></i>حفظ التغييرات
                        </button>
                        <a href="{{ route('admin.orders.index') }}" class="btn-custom btn-cancel">
                            <i class="fas fa-times"></i>إلغاء
                        </a>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>