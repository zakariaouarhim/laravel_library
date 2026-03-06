<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>الإعدادات</title>

    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/settings.css') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    
</head>
<body>

<div class="dashboard_layout">
<div class="container-fluid">
    <div class="row">
        @include('Dashbord_Admin.Sidebar')

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="dashboard-header">
                <h1>
                    <i class="fas fa-cog"></i>
                    الإعدادات
                </h1>
            </div>

            @if(session('success'))
                <div class="alert-success-custom">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert-danger-custom">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>يرجى تصحيح الأخطاء التالية:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf

                {{-- Store Info --}}
                <div class="settings-card">
                    <h3><i class="fas fa-store"></i>معلومات المتجر</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">اسم المتجر</label>
                            <input type="text" name="store_name" class="form-control" value="{{ old('store_name', $settings['store_name']) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رقم الهاتف</label>
                            <input type="text" name="store_phone" class="form-control" value="{{ old('store_phone', $settings['store_phone']) }}" placeholder="0600000000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" name="store_email" class="form-control" value="{{ old('store_email', $settings['store_email']) }}" placeholder="info@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">العنوان</label>
                            <input type="text" name="store_address" class="form-control" value="{{ old('store_address', $settings['store_address']) }}" placeholder="المدينة، الحي، الشارع">
                        </div>
                    </div>
                </div>

                {{-- Shipping --}}
                <div class="settings-card">
                    <h3><i class="fas fa-truck"></i>الشحن</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">تكلفة الشحن</label>
                            <div class="input-group">
                                <input type="number" name="shipping_cost" class="form-control" step="0.01" min="0" value="{{ old('shipping_cost', $settings['shipping_cost']) }}">
                                <span class="input-group-text">د.م</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">حد الشحن المجاني</label>
                            <div class="input-group">
                                <input type="number" name="free_shipping_threshold" class="form-control" step="0.01" min="0" value="{{ old('free_shipping_threshold', $settings['free_shipping_threshold']) }}">
                                <span class="input-group-text">د.م</span>
                            </div>
                            <div class="input-hint">أدخل 0 لتعطيل الشحن المجاني</div>
                        </div>
                    </div>
                </div>

                {{-- Social Links --}}
                <div class="settings-card">
                    <h3><i class="fas fa-share-alt"></i>روابط التواصل الاجتماعي</h3>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label"><i class="fab fa-facebook text-primary"></i> فيسبوك</label>
                            <input type="url" name="facebook_url" class="form-control" value="{{ old('facebook_url', $settings['facebook_url']) }}" placeholder="https://facebook.com/...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><i class="fab fa-instagram text-danger"></i> إنستغرام</label>
                            <input type="url" name="instagram_url" class="form-control" value="{{ old('instagram_url', $settings['instagram_url']) }}" placeholder="https://instagram.com/...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><i class="fab fa-whatsapp" style="color: #25D366;"></i> واتساب</label>
                            <input type="text" name="whatsapp_number" class="form-control" value="{{ old('whatsapp_number', $settings['whatsapp_number']) }}" placeholder="212600000000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><i class="fab fa-tiktok"></i> تيك توك</label>
                            <input type="url" name="tiktok_url" class="form-control" value="{{ old('tiktok_url', $settings['tiktok_url']) }}" placeholder="https://tiktok.com/@...">
                        </div>
                    </div>
                </div>

                {{-- Store Policies --}}
                <div class="settings-card">
                    <h3><i class="fas fa-shield-alt"></i>سياسات المتجر</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">الحد الأدنى للطلب</label>
                            <div class="input-group">
                                <input type="number" name="min_order_amount" class="form-control" step="0.01" min="0" value="{{ old('min_order_amount', $settings['min_order_amount']) }}">
                                <span class="input-group-text">د.م</span>
                            </div>
                            <div class="input-hint">أدخل 0 لعدم وضع حد أدنى</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الحد الأقصى للكمية لكل منتج</label>
                            <input type="number" name="max_quantity_per_item" class="form-control" min="1" max="100" value="{{ old('max_quantity_per_item', $settings['max_quantity_per_item']) }}">
                        </div>
                    </div>
                </div>

                <div class="text-start mb-4">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save me-2"></i>حفظ الإعدادات
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
