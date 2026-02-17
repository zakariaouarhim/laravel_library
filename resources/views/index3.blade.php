<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مكتبة بيع الكتب</title>
    <!-- Correct CSS linking -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    
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
    @include('header')

    
   <!-- Header with Animated Arabic Letters Background -->
<header class="header-section text-white text-center py-5">
    <!-- Background with falling Arabic letters -->
    <div class="letters-background" id="letters-container"></div>
    
    <!-- Content container with search -->
    <div class="container search-container">
        <h1 class="display-4 fw-bold">ابحث عن كتابك المفضل</h1>
        <p class="lead">ابحث في مجموعتنا الكبيرة من الكتب عبر الأنواع والتصنيفات.</p>
        <form class="d-flex justify-content-center mt-4">
            <input type="text" class="form-control w-50 me-2" placeholder="ابحث عن كتاب بالعنوان، المؤلف، أو النوع">
            <button type="submit" class="btn btn-dark">بحث</button>
        </form>
    </div>
</header>

    
 <!-- Success Modal -->

 <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
    <div id="cartToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <strong class="me-auto">السلة</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
    </div>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="cartToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-primary text-white">
            <strong class="me-auto">إشعار</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>
    <!-- first categories -->
    <div class="categories-section text-center">
    <h2 class="section-title">اكتشف حسب الفئة</h2>
    <div class="category-grid d-flex justify-content-center flex-wrap">
        <!-- بطاقة الفئة -->
        <div class="category-card">
        <img src="{{ asset('images/novels.svg') }}" alt="روايات" class="category-icon">
        <h3>روايات</h3>
        <p>اكتشف أروع الروايات من الأدب العربي والعالمي.</p>
        </div>
        <div class="category-card">
        <img src="{{ asset('images/novels.svg') }}" alt="كتب دينية" class="category-icon">
        <h3>كتب دينية</h3>
        <p>تعرف على الكتب التي تقربك من الإيمان.</p>
        </div>
        <div class="category-card">
        <img src="{{ asset('images/novels.svg') }}" alt="تنمية ذاتية" class="category-icon">
        <h3>تنمية ذاتية</h3>
        <p>كتب تحفزك لتحقيق أفضل نسخة من نفسك.</p>
        </div>
        <div class="category-card">
        <img src="{{ asset('images/novels.svg') }}" alt="قصص الأطفال" class="category-icon">
        <h3>قصص الأطفال</h3>
        <p>قصص ممتعة ومفيدة للصغار.</p>
        </div>
    </div>
    </div>
    <!-- end first categories -->


    <!-- ********************************** carousel number 2 ************************************************** -->
   

    <!-- ********************************** end carousel number 2 ************************************************** -->

    <!-- $$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ second categories $$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ -->
    <section>
        <div class="categories-section text-center">
        
        <div class="category-grid d-flex justify-content-center flex-wrap">
            <!-- بطاقة الفئة -->
            <div class="category-card">
            <img src="{{ asset('images/novels.svg') }}" alt="روايات" class="category-icon">
            <h3>فلسفة</h3>
            <p>اكتشف أروع الروايات من الأدب العربي والعالمي.</p>
            </div>
            <div class="category-card">
            <img src="{{ asset('images/novels.svg') }}" alt="كتب دينية" class="category-icon">
            <h3> كتب الفكر</h3>
            <p>تعرف على الكتب التي تقربك من الإيمان.</p>
            </div>
            <div class="category-card">
            <img src="{{ asset('images/novels.svg') }}" alt="تنمية ذاتية" class="category-icon">
            <h3>علم النفس</h3>
            <p>كتب تحفزك لتحقيق أفضل نسخة من نفسك.</p>
            </div>
            <div class="category-card">
            <img src="{{ asset('images/novels.svg') }}" alt="قصص الأطفال" class="category-icon">
            <h3> علم الاجتماع</h3>
            <p>قصص ممتعة ومفيدة للصغار.</p>
            </div>
        </div>
        </div>
    </section>  
    <!--$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ end second categories $$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ -->
    

        
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ asset('js/header.js') }}"></script>
    
    <footer>
        @include('footer')
    </footer>
        
    
    



</body>
</html>


