<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'مكتبة الفقراء')</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; direction: rtl; color: #333; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 40px auto; padding: 0 10px; }
        .card { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
        .header { background: linear-gradient(135deg, #2C4B79 0%, #48CAE4 100%); padding: 36px 40px; text-align: center; }
        .header h1 { color: #fff; font-size: 1.3rem; font-weight: 700; margin: 0; }
        .header .header-icon { font-size: 48px; color: #fff; margin-bottom: 10px; }
        .body-content { padding: 40px; line-height: 1.8; color: #555; }
        .body-content p { margin: 12px 0; font-size: 15px; }
        .name { color: #2C4B79; font-weight: bold; }
        .detail-box { background: #f0f4ff; border-right: 4px solid #2C4B79; border-radius: 8px; padding: 18px 20px; margin: 20px 0; }
        .detail-box h2 { font-size: 16px; color: #333; margin: 0 0 15px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e8ecf0; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #777; font-size: 14px; }
        .detail-value { font-weight: 600; color: #333; font-size: 14px; }
        .detail-value.total { color: #2C4B79; font-size: 16px; }
        .btn { display: inline-block; background: linear-gradient(135deg, #2C4B79, #48CAE4); color: #fff !important; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-weight: 700; font-size: 1rem; }
        .btn-center { text-align: center; margin: 24px 0; }
        .warning-box { background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; border-radius: 8px; margin: 20px 0; font-size: 13px; line-height: 1.7; }
        .info-box { padding: 15px; border-radius: 8px; margin: 20px 0; font-size: 14px; line-height: 1.7; }
        .info-box.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .info-box.danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info-box.info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .alt-link { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 15px; word-break: break-all; }
        .alt-link p { margin: 5px 0; font-size: 13px; color: #666; }
        .alt-link a { color: #2C4B79; text-decoration: none; font-size: 12px; }
        .note-section { background: #e8f4fd; border-right: 3px solid #48CAE4; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .note-section h3 { font-size: 14px; color: #333; margin: 0 0 8px 0; }
        .note-section p { font-size: 14px; margin: 0; }
        .book-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; background: #f0f4ff; border-radius: 6px; margin-bottom: 6px; }
        .book-name { flex: 1; font-weight: 600; color: #333; font-size: 14px; }
        .book-qty { color: #777; font-size: 13px; margin: 0 15px; }
        .book-price { font-weight: 600; color: #2C4B79; font-size: 14px; }
        .status-arrow { display: flex; align-items: center; justify-content: center; gap: 12px; margin: 10px 0; font-size: 16px; }
        .status-new { background: #2C4B79; color: #fff; padding: 6px 16px; border-radius: 20px; font-weight: 700; font-size: 14px; }
        .status-old { background: #e0e0e0; color: #666; padding: 6px 16px; border-radius: 20px; font-size: 14px; }
        .delivery-estimate { text-align: center; background: #e8f5e9; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .delivery-estimate .label { font-size: 13px; color: #666; }
        .delivery-estimate .date { font-size: 18px; font-weight: 700; color: #2e7d32; margin-top: 4px; }
        .footer { background: #f8f9fa; padding: 20px 40px; text-align: center; }
        .footer p { font-size: .82rem; color: #999; line-height: 1.6; margin: 0; }
        @media (max-width: 600px) {
            .body-content, .footer { padding: 24px 20px; }
            .header { padding: 28px 20px; }
            .wrapper { margin: 20px auto; }
        }
    </style>
    @yield('extra-styles')
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header @yield('header-class')">
                @hasSection('header-icon')
                    <div class="header-icon">@yield('header-icon')</div>
                @endif
                <h1>@yield('header-title', 'مكتبة الفقراء')</h1>
            </div>

            <div class="body-content">
                @yield('content')

                <p>شكراً لك،<br>
                <strong>فريق مكتبة الفقراء</strong></p>
            </div>

            <div class="footer">
                <p>&copy; {{ date('Y') }} مكتبة الفقراء. جميع الحقوق محفوظة.</p>
                @yield('footer-extra')
            </div>
        </div>
    </div>
</body>
</html>
