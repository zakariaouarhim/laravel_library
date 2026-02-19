<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - غير مصرح</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
        }
        .error-container { max-width: 500px; padding: 2rem; }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            color: #e74c3c;
            line-height: 1;
            margin-bottom: 1rem;
        }
        .error-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        .error-message {
            color: #7f8c8d;
            font-size: 1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .btn-home {
            display: inline-block;
            background: #3498db;
            color: #fff;
            padding: .8rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background .2s;
        }
        .btn-home:hover { background: #2980b9; color: #fff; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">403</div>
        <div class="error-title">غير مصرح بالوصول</div>
        <p class="error-message">ليس لديك الصلاحية للوصول إلى هذه الصفحة.</p>
        <a href="{{ url('/') }}" class="btn-home">العودة إلى الرئيسية</a>
    </div>
</body>
</html>
