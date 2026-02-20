<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اتصل بنا - مكتبة الفقراء</title>
    @include('partials.meta-tags', [
        'metaTitle' => 'اتصل بنا - مكتبة الفقراء',
        'metaDescription' => 'تواصل مع مكتبة الفقراء. نحن هنا لمساعدتك والإجابة على جميع استفساراتك.',
    ])

    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contact.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Tajawal -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    @include('header')

    <!-- Hero -->
    <div class="contact-hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">اتصل بنا</h1>
                <p class="hero-subtitle">نسعد بتواصلك معنا ونحن هنا لمساعدتك</p>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('index.page') }}"><i class="fas fa-home"></i> الرئيسية</a></li>
                        <li class="breadcrumb-item active">اتصل بنا</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <div class="contact-grid">

            <!-- Contact Form -->
            <div class="contact-form-card">
                <div class="card-header-custom">
                    <i class="fas fa-paper-plane"></i>
                    <h3>أرسل لنا رسالة</h3>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('contact.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="name">
                            <i class="fas fa-user"></i> الاسم الكامل
                        </label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="أدخل اسمك الكامل" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> البريد الإلكتروني
                        </label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="example@email.com" value="{{ old('email') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">
                            <i class="fas fa-tag"></i> الموضوع
                        </label>
                        <input type="text" id="subject" name="subject" class="form-control" placeholder="موضوع رسالتك" value="{{ old('subject') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="message">
                            <i class="fas fa-comment-dots"></i> الرسالة
                        </label>
                        <textarea id="message" name="message" class="form-control" rows="5" placeholder="اكتب رسالتك هنا..." required>{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> إرسال الرسالة
                    </button>
                </form>
            </div>

            <!-- Contact Info -->
            <div class="contact-info-side">
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h4>البريد الإلكتروني</h4>
                    <p>info@maktabet-alfuqara.com</p>
                </div>

                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <h4>الهاتف</h4>
                    <p dir="ltr">+212 69 121 8840</p>
                </div>

                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h4>العنوان</h4>
                    <p>المملكة المغربية</p>
                </div>

                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4>ساعات العمل</h4>
                    <p>الثلثاء - الأحد: 10 صباحاً - 8 مساءً</p>
                </div>

                <!-- Social Links -->
                <div class="social-section">
                    <h4>تابعنا</h4>
                    <div class="social-links">
                        <a href="https://www.facebook.com/maktabatalfokara" target="_blank" rel="noopener noreferrer" class="social-link">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://www.instagram.com/maktabat_lfokara" target="_blank" rel="noopener noreferrer" class="social-link">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://wa.me/212691218840" target="_blank" rel="noopener noreferrer" class="social-link">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="https://www.tiktok.com/@maktabatalfokara" target="_blank" rel="noopener noreferrer" class="social-link">
                            <i class="fab fa-tiktok"></i>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @include('footer')
</body>
</html>
