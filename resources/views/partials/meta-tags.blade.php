{{-- SEO Meta Tags --}}
<meta name="description" content="{{ $metaDescription ?? 'مكتبة الفقراء - متجر إلكتروني لبيع الكتب بأسعار مناسبة. اكتشف تشكيلة واسعة من الكتب العربية والمترجمة.' }}">

{{-- Open Graph --}}
<meta property="og:type" content="{{ $metaType ?? 'website' }}">
<meta property="og:title" content="{{ $metaTitle ?? 'مكتبة الفقراء' }}">
<meta property="og:description" content="{{ $metaDescription ?? 'مكتبة الفقراء - متجر إلكتروني لبيع الكتب بأسعار مناسبة. اكتشف تشكيلة واسعة من الكتب العربية والمترجمة.' }}">
<meta property="og:url" content="{{ $metaUrl ?? url()->current() }}">
<meta property="og:image" content="{{ $metaImage ?? asset('images/logo.svg') }}">
<meta property="og:locale" content="ar_AR">
<meta property="og:site_name" content="مكتبة الفقراء">

{{-- Twitter Card --}}
<meta name="twitter:card" content="{{ isset($metaImage) ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $metaTitle ?? 'مكتبة الفقراء' }}">
<meta name="twitter:description" content="{{ $metaDescription ?? 'مكتبة الفقراء - متجر إلكتروني لبيع الكتب بأسعار مناسبة.' }}">
<meta name="twitter:image" content="{{ $metaImage ?? asset('images/logo.svg') }}">
