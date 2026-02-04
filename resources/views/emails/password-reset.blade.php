<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور</title>
    <link rel="stylesheet" href="{{ asset('css/emails/password-reset.css') }}">
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>إعادة تعيين كلمة المرور</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <p>مرحباً <span class="name">{{ $userName }}</span>,</p>
            
            <p>لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بك. اضغط على الزر أدناه لإعادة تعيين كلمة المرور الخاصة بك.</p>

            <!-- Reset Button -->
            <div class="reset-button">
                <a href="{{ $resetLink }}">إعادة تعيين كلمة المرور</a>
            </div>

            <!-- Alternative Link -->
            <div class="alternative-link">
                <p><strong>أو نسخ واللصق هذا الرابط في متصفحك:</strong></p>
                <p><a href="{{ $resetLink }}">{{ $resetLink }}</a></p>
            </div>

            <!-- Security Warning -->
            <div class="warning">
                ⚠️ <strong>ملاحظة أمان:</strong> هذا الرابط سينتهي صلاحيته خلال ساعة واحدة. إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذا البريد الإلكتروني.
            </div>

            <p>إذا كان لديك أي مشاكل في إعادة تعيين كلمة المرور الخاصة بك، يرجى الاتصال بفريق الدعم لدينا.</p>
            
            <p>شكراً لك،<br>
            <strong>فريق المكتبة الرقمية</strong></p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© {{ date('Y') }} جميع الحقوق محفوظة. هذا البريد الإلكتروني آمن وسري.</p>
            <p>إذا كنت لم تطلب هذا البريد الإلكتروني، يرجى حذفه فوراً.</p>
        </div>
    </div>
</body>
</html>