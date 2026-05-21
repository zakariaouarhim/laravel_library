<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <x-seo.head
        :title="$seo['title'] ?? null"
        :description="$seo['description'] ?? null"
        :canonical="$seo['canonical'] ?? null"
        :image="$seo['image'] ?? null"
        :type="$seo['type'] ?? 'website'"
        :robots="$seo['robots'] ?? null"
    />

    @if(config('seo.gsc_token'))
    <meta name="google-site-verification" content="{{ config('seo.gsc_token') }}">
    @endif

    {{-- Common CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    {{-- Per-page CSS / fonts --}}
    @stack('styles')

    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="auth-user" content="true">
    @endauth

    {{-- Per-page extra <head> content (schema, analytics, preloads) --}}
    @stack('head')

    @if(config('seo.ga4_id') && app()->environment('production'))
    {{-- Google Analytics 4 --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('seo.ga4_id') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ config('seo.ga4_id') }}');
    </script>
    @endif
</head>
<body>
    @include('header')

    @yield('content')

    <footer>
        @include('footer')
    </footer>

    {{-- Per-page JS --}}
    @stack('scripts')
</body>
</html>
