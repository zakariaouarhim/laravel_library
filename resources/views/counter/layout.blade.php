<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="{{ asset('js/csrf.js') }}" defer></script>
    {{-- "Add to Home Screen" so the tablet launches this like an app --}}
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0d6efd">
    <title>@yield('title', 'شباك الطلبات')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body {
            background: #f2f4f8;
            /* Larger base font: this UI is read at arm's length on a counter */
            font-size: 1.15rem;
            -webkit-tap-highlight-color: transparent;
        }
        .btn-xl {
            padding: .85rem 1.5rem;
            font-size: 1.35rem;
            font-weight: 700;
            border-radius: .9rem;
        }
        .counter-nav .nav-link {
            font-size: 1.25rem;
            font-weight: 700;
            padding: .8rem 1.4rem;
            border-radius: .75rem;
        }
        .counter-nav .nav-link.active { background: #0d6efd; color: #fff; }
        /* Huge digits for order number / shelf */
        .num-huge { font-size: 3.2rem; font-weight: 800; line-height: 1.1; }
        .num-big  { font-size: 2rem;   font-weight: 800; line-height: 1.1; }
    </style>
    @yield('head')
</head>
<body>

<nav class="bg-white border-bottom shadow-sm mb-3">
    <div class="container py-2 d-flex align-items-center gap-2 counter-nav">
        <span class="fw-bold fs-4 text-primary ms-2">📚 شباك الطلبات</span>
        <a class="nav-link {{ request()->routeIs('counter.index') ? 'active' : 'text-dark' }}"
           href="{{ route('counter.index') }}">🔍 بحث</a>
        <a class="nav-link {{ request()->routeIs('counter.add') ? 'active' : 'text-dark' }}"
           href="{{ route('counter.add') }}">➕ طلب جديد</a>
    </div>
</nav>

<main class="container pb-5" style="max-width: 860px;">
    @yield('content')
</main>

@yield('scripts')
</body>
</html>
