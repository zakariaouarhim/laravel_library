<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مكتبة بيع الكتب</title>
    <!-- Correct CSS linking -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
   <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet"> 
   

</head>
<body>
 <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="{{ route('index.page') }}">
            <img src="{{ asset('images/logo.svg') }}" alt="شعار المكتبة" class="d-inline-block align-text-top">
            <span class="ms-2"></span>
        </a>
        <!-- Toggler for mobile view -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ms-auto">
                <!-- Dropdown for Arabic Books -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="arabicBooksDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        الكتب العربية
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="arabicBooksDropdown">
                        <li><a class="dropdown-item" href="#fiction-arabic">روايات</a></li>
                        <li><a class="dropdown-item" href="#science-arabic">الكتب العلمية</a></li>
                        <li><a class="dropdown-item" href="#history-arabic">التاريخ</a></li>
                        <li><a class="dropdown-item" href="#children-arabic">كتب الأطفال</a></li>
                    </ul>
                </li>

                <!-- Dropdown for English Books -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="englishBooksDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        الكتب الإنجليزية
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="englishBooksDropdown">
                        <li><a class="dropdown-item" href="#fiction-english">Fiction</a></li>
                        <li><a class="dropdown-item" href="#science-english">Science</a></li>
                        <li><a class="dropdown-item" href="#history-english">History</a></li>
                        <li><a class="dropdown-item" href="#children-english">Children's Books</a></li>
                    </ul>
                </li>

                <!-- Static Links -->
                <li class="nav-item">
                    <a class="nav-link" href="#bookCarousel2" >الأكثر مبيعًا</a>

                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#bookCarousel">الإصدارات الحديثة</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#collections">المجموعات</a>
                </li>
                
            </ul>
        </div>
    </div>
</nav>
</body>
</html>


















