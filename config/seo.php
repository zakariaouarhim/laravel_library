<?php

return [
    'site_name'           => env('SEO_SITE_NAME', 'مكتبة الفقراء'),
    'default_title'       => env('SEO_DEFAULT_TITLE', 'مكتبة الفقراء - كتب بأسعار مناسبة للجميع'),
    'default_description' => env('SEO_DEFAULT_DESCRIPTION', 'مكتبة الفقراء - متجر إلكتروني لبيع الكتب بأسعار مناسبة. اكتشف تشكيلة واسعة من الكتب العربية والمترجمة في مختلف المجالات.'),
    'default_image'       => env('SEO_DEFAULT_IMAGE', 'images/logo.svg'),
    'locale'              => 'ar_AR',
    'twitter_card'        => 'summary_large_image',
    'twitter_handle'      => env('SEO_TWITTER_HANDLE'),

    // Google Search Console verification token (raw value, not full meta tag)
    'gsc_token'           => env('GOOGLE_SITE_VERIFICATION'),
    // GA4 measurement id; analytics partial is only emitted in production when set
    'ga4_id'              => env('GA4_MEASUREMENT_ID'),

    'meta_title_max'       => 70,
    'meta_description_max' => 160,
];
