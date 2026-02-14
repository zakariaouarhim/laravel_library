<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>من نحن - مكتبة الفقراء</title>
    <meta name="description" content="تعرف على مكتبة الفقراء، رسالتنا وقيمنا في نشر المعرفة وتوفير الكتب بأسعار مناسبة للجميع.">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/about.css') }}">
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
    <div class="about-hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">من نحن</h1>
                <p class="hero-subtitle">نؤمن بأن المعرفة حق للجميع</p>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('index.page') }}"><i class="fas fa-home"></i> الرئيسية</a></li>
                        <li class="breadcrumb-item active">من نحن</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Story Section -->
    <section class="about-story">
        <div class="container">
            <div class="story-grid">
                <div class="story-content">
                    <div class="section-badge">
                        <i class="fas fa-book-open"></i>
                        <span>قصتنا</span>
                    </div>
                    <h2 class="story-title">مكتبة الفقراء... معرفة بلا حدود</h2>
                    <p class="story-text">
                        انطلقت مكتبة الفقراء من فكرة بسيطة: أن الكتاب يجب أن يكون في متناول الجميع.
                        نسعى لتوفير أفضل الكتب والمؤلفات بأسعار مناسبة، لأن المعرفة لا ينبغي أن تكون حكراً على فئة دون أخرى.
                    </p>
                    <p class="story-text">
                        نعمل على جمع وتوفير مجموعة متنوعة من الكتب في مختلف المجالات،
                        من الأدب والفلسفة إلى العلوم والتاريخ، مع الحرص على جودة المحتوى وسهولة الوصول إليه.
                    </p>
                </div>
                <div class="story-image">
                    <div class="image-placeholder">
                        <i class="fas fa-books"></i>
                        <div class="floating-card card-1">
                            <i class="fas fa-heart"></i>
                            <span>شغف بالقراءة</span>
                        </div>
                        <div class="floating-card card-2">
                            <i class="fas fa-globe-africa"></i>
                            <span>معرفة عالمية</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="about-features">
        <div class="container">
            <div class="text-center mb-5">
                <div class="section-badge centered">
                    <i class="fas fa-star"></i>
                    <span>لماذا نحن</span>
                </div>
                <h2 class="features-title">ما يميزنا</h2>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3>تشكيلة واسعة</h3>
                    <p>مجموعة متنوعة من الكتب في مختلف المجالات والتخصصات تناسب جميع الأذواق والاهتمامات.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h3>أسعار مناسبة</h3>
                    <p>نحرص على تقديم أسعار تنافسية تجعل المعرفة في متناول الجميع دون استثناء.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h3>توصيل سريع</h3>
                    <p>خدمة توصيل سريعة وآمنة لجميع أنحاء المملكة لتصلك كتبك أينما كنت.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-undo-alt"></i>
                    </div>
                    <h3>سهولة الإرجاع</h3>
                    <p>سياسة إرجاع مرنة وسهلة لضمان رضاك التام عن تجربة الشراء.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>دفع آمن</h3>
                    <p>طرق دفع متعددة وآمنة لحماية بياناتك وضمان تجربة شراء مريحة.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>دعم متواصل</h3>
                    <p>فريق خدمة عملاء متاح لمساعدتك والإجابة على استفساراتك في أي وقت.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="about-stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">{{ number_format($stats['books']) }}+</div>
                    <div class="stat-label">كتاب متوفر</div>
                    <div class="stat-icon"><i class="fas fa-book"></i></div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">{{ number_format($stats['authors']) }}+</div>
                    <div class="stat-label">مؤلف</div>
                    <div class="stat-icon"><i class="fas fa-feather-alt"></i></div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">{{ number_format($stats['categories']) }}+</div>
                    <div class="stat-label">تصنيف</div>
                    <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">خدمة متواصلة</div>
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="about-cta">
        <div class="container">
            <div class="cta-content">
                <h2>ابدأ رحلتك مع القراءة اليوم</h2>
                <p>اكتشف مجموعتنا الواسعة من الكتب وابدأ رحلة المعرفة</p>
                <div class="cta-buttons">
                    <a href="{{ route('index.page') }}" class="cta-btn primary">
                        <i class="fas fa-book-open"></i> تصفح الكتب
                    </a>
                    <a href="{{ route('contact.page') }}" class="cta-btn secondary">
                        <i class="fas fa-envelope"></i> تواصل معنا
                    </a>
                </div>
            </div>
        </div>
    </section>

    @include('footer')
</body>
</html>
