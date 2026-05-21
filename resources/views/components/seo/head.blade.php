@props([
    'title'       => null,
    'description' => null,
    'canonical'   => null,
    'image'       => null,
    'type'        => 'website',
    'robots'      => null,
])

@php
    $resolvedTitle       = $title       ?? config('seo.default_title');
    $resolvedDescription = $description ?? config('seo.default_description');
    $resolvedCanonical   = $canonical   ?? url()->current();
    $resolvedImage       = $image       ?? asset(config('seo.default_image'));

    // Ensure image is an absolute URL — OG/Twitter scrapers fail on relative paths.
    if (!preg_match('/^https?:\\/\\//i', $resolvedImage)) {
        $resolvedImage = asset(ltrim($resolvedImage, '/'));
    }
@endphp

<title>{{ $resolvedTitle }}</title>
<meta name="description" content="{{ $resolvedDescription }}">
<link rel="canonical" href="{{ $resolvedCanonical }}">
@if($robots)
<meta name="robots" content="{{ $robots }}">
@endif

{{-- Open Graph --}}
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $resolvedTitle }}">
<meta property="og:description" content="{{ $resolvedDescription }}">
<meta property="og:url" content="{{ $resolvedCanonical }}">
<meta property="og:image" content="{{ $resolvedImage }}">
<meta property="og:locale" content="{{ config('seo.locale') }}">
<meta property="og:site_name" content="{{ config('seo.site_name') }}">

{{-- Twitter --}}
<meta name="twitter:card" content="{{ config('seo.twitter_card') }}">
<meta name="twitter:title" content="{{ $resolvedTitle }}">
<meta name="twitter:description" content="{{ $resolvedDescription }}">
<meta name="twitter:image" content="{{ $resolvedImage }}">
@if($handle = config('seo.twitter_handle'))
<meta name="twitter:site" content="{{ $handle }}">
@endif
