<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الطلب - أسير الكتب</title>

    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/order-manage.css') }}">
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    @include('header')

    <div class="manage-container">
        <div class="manage-wrapper">

            <!-- Alerts -->
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <!-- Page Header -->
            <div class="manage-header">
                <div class="manage-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <h1>إدارة الطلب</h1>
                <p>يمكنك من هنا متابعة طلبك وإدارته</p>
            </div>

            <!-- Order Status Card -->
            <div class="manage-card">
                <h2 class="card-title"><i class="fas fa-info-circle"></i> معلومات الطلب</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">رقم الطلب</span>
                        <span class="info-value">#{{ $order->id }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">رقم التتبع</span>
                        <span class="info-value">{{ $order->tracking_number }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">طريقة الدفع</span>
                        <span class="info-value">{{ $order->payment_method == 'cod' ? 'الدفع عند الاستلام' : 'بطاقة ائتمان' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">المبلغ الإجمالي</span>
                        <span class="info-value total-price">{{ number_format($order->total_price, 2) }} د.م</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">حالة الطلب</span>
                        <span class="info-value">
                            @php
                                $statusMap = [
                                    'pending' => ['class' => 'status-pending', 'text' => 'قيد المعالجة'],
                                    'processing' => ['class' => 'status-processing', 'text' => 'جاري التجهيز'],
                                    'shipped' => ['class' => 'status-shipped', 'text' => 'تم الشحن'],
                                    'delivered' => ['class' => 'status-delivered', 'text' => 'تم التوصيل'],
                                    'cancelled' => ['class' => 'status-cancelled', 'text' => 'ملغي'],
                                    'Failed' => ['class' => 'status-failed', 'text' => 'فشل'],
                                    'Refunded' => ['class' => 'status-refunded', 'text' => 'تم الاسترداد'],
                                    'returned' => ['class' => 'status-returned', 'text' => 'مسترجع'],
                                ];
                                $s = $statusMap[$order->status] ?? ['class' => 'status-pending', 'text' => $order->status];
                            @endphp
                            <span class="status-badge {{ $s['class'] }}">{{ $s['text'] }}</span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">تاريخ الطلب</span>
                        <span class="info-value">{{ $order->created_at->format('d-m-Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Customer Info -->
            @if($order->checkoutDetail)
            <div class="manage-card">
                <h2 class="card-title"><i class="fas fa-user"></i> معلومات العميل</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">الاسم</span>
                        <span class="info-value">{{ $order->checkoutDetail->first_name }} {{ $order->checkoutDetail->last_name }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">البريد الإلكتروني</span>
                        <span class="info-value">{{ $order->checkoutDetail->email }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">الهاتف</span>
                        <span class="info-value">{{ $order->checkoutDetail->phone }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">العنوان</span>
                        <span class="info-value">{{ $order->checkoutDetail->address }}، {{ $order->checkoutDetail->city }}</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Order Items -->
            <div class="manage-card">
                <h2 class="card-title"><i class="fas fa-shopping-bag"></i> الكتب المطلوبة</h2>
                <div class="books-list">
                    @foreach($order->orderDetails as $item)
                    <div class="book-item">
                        <div class="book-info">
                            <div class="book-title">{{ $item->book ? $item->book->title : 'كتاب #'.$item->book_id }}</div>
                            <div class="book-quantity">الكمية: {{ $item->quantity }}</div>
                        </div>
                        <div class="book-price">{{ number_format($item->price * $item->quantity, 2) }} د.م</div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Actions Section -->
            <div class="manage-card actions-card">
                <h2 class="card-title"><i class="fas fa-tools"></i> إجراءات الطلب</h2>

                @if(in_array($order->status, ['pending', 'processing']))
                <!-- Cancel Order -->
                <div class="action-section">
                    <div class="action-info">
                        <h3><i class="fas fa-times-circle text-danger"></i> إلغاء الطلب</h3>
                        <p>يمكنك إلغاء الطلب لأنه لا يزال {{ $order->status == 'pending' ? 'قيد المعالجة' : 'جاري التجهيز' }}.</p>
                    </div>
                    <form action="{{ route('order.manage.cancel') }}" method="POST" onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الطلب؟')">
                        @csrf
                        <input type="hidden" name="token" value="{{ $order->management_token }}">
                        <button type="submit" class="btn btn-cancel">
                            <i class="fas fa-times-circle me-2"></i>إلغاء الطلب
                        </button>
                    </form>
                </div>
                @endif

                @if($order->status === 'delivered' && !$hasActiveReturn)
                <!-- Return Request -->
                <div class="action-section">
                    <div class="action-info">
                        <h3><i class="fas fa-undo text-primary"></i> طلب إسترجاع</h3>
                        <p>يمكنك طلب إسترجاع الطلب لأنه تم توصيله.</p>
                    </div>
                    <form action="{{ route('order.manage.return') }}" method="POST">
                        @csrf
                        <input type="hidden" name="token" value="{{ $order->management_token }}">
                        <div class="mb-3">
                            <label class="form-label fw-bold">سبب الإرجاع</label>
                            <textarea name="reason" class="form-control" rows="3" placeholder="اكتب سبب الإرجاع هنا..." required maxlength="1000">{{ old('reason') }}</textarea>
                            @error('reason')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-return">
                            <i class="fas fa-undo me-2"></i>إرسال طلب الإسترجاع
                        </button>
                    </form>
                </div>
                @endif

                @if($hasActiveReturn)
                <div class="action-section">
                    <div class="active-return-notice">
                        <i class="fas fa-info-circle"></i>
                        يوجد طلب إسترجاع قيد المعالجة لهذا الطلب. لا يمكنك إرسال طلب جديد حالياً.
                    </div>
                </div>
                @endif

                @if(in_array($order->status, ['cancelled', 'Failed', 'Refunded', 'returned']))
                <div class="action-section">
                    <div class="no-actions-notice">
                        <i class="fas fa-info-circle"></i>
                        لا توجد إجراءات متاحة لهذا الطلب حالياً.
                    </div>
                </div>
                @endif

                @if($order->status === 'shipped')
                <div class="action-section">
                    <div class="no-actions-notice">
                        <i class="fas fa-truck"></i>
                        طلبك في الطريق إليك! بعد استلام الطلب يمكنك طلب إسترجاع إذا لزم الأمر.
                    </div>
                </div>
                @endif
            </div>

            <!-- Return Requests History -->
            @if($returnRequests->count())
            <div class="manage-card">
                <h2 class="card-title"><i class="fas fa-history"></i> طلبات الإسترجاع</h2>
                @foreach($returnRequests as $returnReq)
                <div class="return-item">
                    <div class="return-header">
                        <span class="return-id">#{{ $returnReq->id }}</span>
                        @php
                            $retStatusMap = [
                                'pending'  => ['class' => 'ret-pending',  'text' => 'قيد المراجعة'],
                                'approved' => ['class' => 'ret-approved', 'text' => 'مقبول'],
                                'rejected' => ['class' => 'ret-rejected', 'text' => 'مرفوض'],
                                'refunded' => ['class' => 'ret-refunded', 'text' => 'تم الاسترداد'],
                            ];
                            $rs = $retStatusMap[$returnReq->status] ?? ['class' => 'ret-pending', 'text' => $returnReq->status];
                        @endphp
                        <span class="return-status {{ $rs['class'] }}">{{ $rs['text'] }}</span>
                    </div>
                    <div class="return-reason">
                        <strong>السبب:</strong> {{ $returnReq->reason }}
                    </div>
                    @if($returnReq->admin_notes)
                    <div class="return-admin-notes">
                        <strong>ملاحظات الإدارة:</strong> {{ $returnReq->admin_notes }}
                    </div>
                    @endif
                    <div class="return-meta">
                        <span>المبلغ: {{ number_format($returnReq->refund_amount, 2) }} د.م</span>
                        <span>التاريخ: {{ $returnReq->created_at->format('d-m-Y H:i') }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Back -->
            <div class="text-center mt-4 mb-5">
                <a href="{{ route('index.page') }}" class="btn btn-back">
                    <i class="fas fa-home me-2"></i>العودة للرئيسية
                </a>
            </div>

        </div>
    </div>

    @include('footer')

    
    <script src="{{ asset('js/header.js') }}"></script>
</body>
</html>
