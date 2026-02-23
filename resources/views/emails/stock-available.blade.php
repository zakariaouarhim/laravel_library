<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f0f4f8; direction: rtl; color: #333; }
        .wrapper { max-width: 600px; margin: 40px auto; }
        .card { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
        .header { background: linear-gradient(135deg, #2C4B79 0%, #48CAE4 100%); padding: 36px 40px; text-align: center; }
        .header img { height: 36px; margin-bottom: 12px; }
        .header h1 { color: #fff; font-size: 1.3rem; font-weight: 700; margin: 0; }
        .body { padding: 40px; }
        .greeting { font-size: 1rem; color: #555; margin-bottom: 20px; line-height: 1.7; }
        .book-box { background: #f0f4ff; border-right: 4px solid #2C4B79; border-radius: 8px; padding: 18px 20px; margin: 24px 0; }
        .book-box .book-title { font-size: 1.1rem; font-weight: 700; color: #1a2f4e; }
        .book-box .book-author { font-size: .9rem; color: #666; margin-top: 6px; }
        .cta-text { font-size: .95rem; color: #555; line-height: 1.7; margin-bottom: 28px; }
        .btn { display: inline-block; background: linear-gradient(135deg, #2C4B79, #48CAE4); color: #fff !important; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-weight: 700; font-size: 1rem; }
        .footer { background: #f8f9fa; padding: 20px 40px; text-align: center; }
        .footer p { font-size: .82rem; color: #999; line-height: 1.6; }
        @media (max-width: 600px) {
            .body, .footer { padding: 24px 20px; }
            .header { padding: 28px 20px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <h1>مكتبة الفقراء</h1>
            </div>

            <div class="body">
                <p class="greeting">مرحباً،<br>
                يسعدنا إخبارك بأن الكتاب الذي طلبت التنبيه عند توفره أصبح متاحاً الآن في مكتبتنا:</p>

                <div class="book-box">
                    <div class="book-title">{{ $book->title }}</div>
                    @if($book->author)
                        <div class="book-author"><i>{{ $book->author }}</i></div>
                    @endif
                </div>

                <p class="cta-text">
                    بادر بزيارة صفحة الكتاب وإضافته إلى سلة التسوق قبل نفاد الكمية مجدداً.
                    الكميات محدودة وقد تنفد بسرعة!
                </p>

                <a href="{{ route('moredetail2.page', $book->id) }}" class="btn">
                    عرض الكتاب والشراء الآن
                </a>
            </div>

            <div class="footer">
                <p>
                    مكتبة الفقراء — المعرفة في متناول الجميع<br>
                    تلقيت هذا البريد لأنك طلبت التنبيه عند توفر هذا الكتاب.<br>
                    إذا لم تطلب ذلك، يمكنك تجاهل هذه الرسالة بأمان.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
