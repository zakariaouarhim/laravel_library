<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سياسة الخصوصية - مكتبة الفقراء</title>
    @include('partials.meta-tags', [
        'metaTitle' => 'سياسة الخصوصية - مكتبة الفقراء',
        'metaDescription' => 'سياسة الخصوصية لمكتبة الفقراء — كيف نجمع بياناتك ونحميها ونستخدمها.',
    ])

    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/about.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
.privacy-body { padding: 60px 0; background: #f8f9fa; }
        .privacy-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,.06);
            padding: 48px 56px;
            max-width: 860px;
            margin: 0 auto;
        }
        .privacy-card .last-updated {
            font-size: .85rem;
            color: #888;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: .4rem;
        }
        .privacy-section { margin-bottom: 2.5rem; }
        .privacy-section h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1a2f4e;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .privacy-section h2 .section-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1a2f4e, #2C4B79);
            color: #fff;
            font-size: .8rem;
            flex-shrink: 0;
        }
        .privacy-section p, .privacy-section li { color: #444; line-height: 1.85; font-size: .97rem; }
        .privacy-section ul { padding-right: 1.2rem; margin-bottom: 0; }
        .privacy-section ul li { margin-bottom: .4rem; }
        .privacy-divider { border: none; border-top: 1px solid #eee; margin: 2rem 0; }
        .privacy-contact-box {
            background: linear-gradient(135deg, #f0f4ff, #e8f0fe);
            border-radius: 12px;
            padding: 24px 28px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }
        .privacy-contact-box i { font-size: 1.6rem; color: #2C4B79; margin-top: 2px; flex-shrink: 0; }
        .privacy-contact-box a { color: #2C4B79; font-weight: 600; text-decoration: none; }
        .privacy-contact-box a:hover { text-decoration: underline; }

        @media (max-width: 768px) {
            .privacy-card { padding: 28px 20px; }
        }
    </style>
</head>
<body>
    @include('header')

    @include('partials.page-hero', [
        'title'       => 'سياسة الخصوصية',
        'subtitle'    => 'نلتزم بحماية بياناتك الشخصية واحترام خصوصيتك',
        'icon'        => 'fas fa-shield-alt',
        'centered'    => true,
        'breadcrumbs' => [
            ['label' => 'الرئيسية', 'url' => route('index.page')],
            ['label' => 'سياسة الخصوصية'],
        ],
    ])

    <!-- Content -->
    <div class="privacy-body">
        <div class="container">
            <div class="privacy-card">

                <div class="last-updated">
                    <i class="fas fa-calendar-alt"></i>
                    آخر تحديث: يناير 2025
                </div>

                <!-- 1 -->
                <div class="privacy-section">
                    <h2><span class="section-num">1</span> مقدمة</h2>
                    <p>
                        تلتزم مكتبة الفقراء بحماية خصوصية زوارها وعملائها. توضح هذه السياسة كيفية جمع بياناتك الشخصية
                        عند استخدامك لموقعنا الإلكتروني، وكيف نستخدمها ونحميها، وفق أحكام القانون المغربي رقم 09-08
                        المتعلق بحماية الأشخاص الذاتيين تجاه معالجة البيانات ذات الطابع الشخصي.
                    </p>
                    <p>
                        باستخدامك لموقعنا أو إجرائك عملية شراء، فإنك توافق على الشروط المبيّنة في هذه السياسة.
                    </p>
                </div>
                <hr class="privacy-divider">

                <!-- 2 -->
                <div class="privacy-section">
                    <h2><span class="section-num">2</span> البيانات التي نجمعها</h2>
                    <p>نجمع البيانات التالية عند تفاعلك مع الموقع:</p>
                    <ul>
                        <li><strong>بيانات الحساب:</strong> الاسم، البريد الإلكتروني، كلمة المرور (مشفرة).</li>
                        <li><strong>بيانات الطلب:</strong> العنوان، رقم الهاتف، المدينة، الرمز البريدي، طريقة الدفع.</li>
                        <li><strong>بيانات الاستخدام:</strong> الصفحات التي تزورها، الكتب التي تتصفحها، محتوى السلة وقائمة الأمنيات.</li>
                        <li><strong>البيانات التقنية:</strong> عنوان IP، نوع المتصفح، الجهاز، نظام التشغيل.</li>
                        <li><strong>الرسائل:</strong> ما تُرسله عبر نموذج التواصل.</li>
                    </ul>
                </div>
                <hr class="privacy-divider">

                <!-- 3 -->
                <div class="privacy-section">
                    <h2><span class="section-num">3</span> كيف نستخدم بياناتك</h2>
                    <p>نستخدم بياناتك حصراً للأغراض التالية:</p>
                    <ul>
                        <li>معالجة طلباتك وتأكيدها وتوصيلها إليك.</li>
                        <li>إرسال تأكيد الطلب وتحديثات الشحن عبر البريد الإلكتروني.</li>
                        <li>إدارة حسابك وتمكينك من متابعة طلباتك وتاريخ الشراء.</li>
                        <li>تحسين تجربة التصفح وتخصيص التوصيات.</li>
                        <li>الرد على استفساراتك وطلبات الدعم.</li>
                        <li>الوفاء بالتزاماتنا القانونية والضريبية.</li>
                    </ul>
                    <p>لا نستخدم بياناتك لأغراض تسويقية دون موافقتك الصريحة.</p>
                </div>
                <hr class="privacy-divider">

                <!-- 4 -->
                <div class="privacy-section">
                    <h2><span class="section-num">4</span> مشاركة البيانات مع أطراف ثالثة</h2>
                    <p>
                        لا نبيع بياناتك الشخصية ولا نتاجر بها. قد نشاركها فقط في الحالات التالية:
                    </p>
                    <ul>
                        <li><strong>شركات الشحن:</strong> اسمك وعنوانك ورقم هاتفك لأغراض التوصيل فقط.</li>
                        <li><strong>مزودو الخدمات التقنية:</strong> استضافة الموقع ومعالجة البريد الإلكتروني، مع ضمان التزامهم بالسرية.</li>
                        <li><strong>الجهات القانونية:</strong> إذا استوجب ذلك حكم قضائي أو طلب رسمي من الجهات المختصة.</li>
                    </ul>
                </div>
                <hr class="privacy-divider">

                <!-- 5 -->
                <div class="privacy-section">
                    <h2><span class="section-num">5</span> ملفات تعريف الارتباط (Cookies)</h2>
                    <p>
                        نستخدم ملفات الارتباط لضمان عمل الموقع بشكل صحيح وتحسين تجربتك. تشمل:
                    </p>
                    <ul>
                        <li><strong>ملفات ضرورية:</strong> للجلسة، السلة، حالة تسجيل الدخول — لا يمكن إيقافها.</li>
                        <li><strong>ملفات الأداء:</strong> لقياس حركة الزيارات وتحسين أداء الموقع.</li>
                    </ul>
                    <p>يمكنك إدارة ملفات الارتباط من إعدادات متصفحك في أي وقت.</p>
                </div>
                <hr class="privacy-divider">

                <!-- 6 -->
                <div class="privacy-section">
                    <h2><span class="section-num">6</span> حماية بياناتك</h2>
                    <p>نتخذ التدابير التقنية والتنظيمية اللازمة لحماية بياناتك، وتشمل:</p>
                    <ul>
                        <li>تشفير كلمات المرور بخوارزمية Bcrypt.</li>
                        <li>اتصال HTTPS مشفر على جميع صفحات الموقع.</li>
                        <li>تقييد الوصول الداخلي إلى البيانات الحساسة.</li>
                        <li>مراجعة دورية لإجراءات الأمان.</li>
                    </ul>
                </div>
                <hr class="privacy-divider">

                <!-- 7 -->
                <div class="privacy-section">
                    <h2><span class="section-num">7</span> مدة الاحتفاظ بالبيانات</h2>
                    <p>
                        نحتفظ ببياناتك طوال فترة نشاط حسابك. عند طلب الحذف، نمسح بياناتك في غضون 30 يوماً،
                        مع الاحتفاظ بما تستوجبه القوانين الضريبية والمحاسبية لمدة لا تتجاوز 10 سنوات.
                    </p>
                </div>
                <hr class="privacy-divider">

                <!-- 8 -->
                <div class="privacy-section">
                    <h2><span class="section-num">8</span> حقوقك</h2>
                    <p>وفق القانون المغربي 09-08، تتمتع بالحقوق التالية:</p>
                    <ul>
                        <li><strong>حق الاطلاع:</strong> الاطلاع على البيانات التي نحتفظ بها عنك.</li>
                        <li><strong>حق التصحيح:</strong> تصحيح أي بيانات غير دقيقة.</li>
                        <li><strong>حق الحذف:</strong> طلب حذف حسابك وبياناتك.</li>
                        <li><strong>حق الاعتراض:</strong> الاعتراض على معالجة بياناتك لأغراض تسويقية.</li>
                    </ul>
                    <p>لممارسة أي من هذه الحقوق، تواصل معنا عبر البريد أدناه.</p>
                </div>
                <hr class="privacy-divider">

                <!-- 9 -->
                <div class="privacy-section">
                    <h2><span class="section-num">9</span> تعديل هذه السياسة</h2>
                    <p>
                        نحتفظ بحق تعديل هذه السياسة في أي وقت. سيُعلَم المستخدمون المسجلون بأي تغييرات جوهرية
                        عبر البريد الإلكتروني. تاريخ آخر تحديث مذكور في أعلى هذه الصفحة.
                    </p>
                </div>
                <hr class="privacy-divider">

                <!-- Contact -->
                <div class="privacy-contact-box">
                    <i class="fas fa-envelope-open-text"></i>
                    <div>
                        <strong style="color:#1a2f4e;">للاستفسار أو ممارسة حقوقك</strong>
                        <p style="margin:.4rem 0 0;color:#555;font-size:.95rem;">
                            راسلنا على
                            <a href="mailto:info@maktaba-fukara.com">info@maktaba-fukara.com</a>
                            أو عبر <a href="{{ route('contact.page') }}">نموذج التواصل</a>.
                            سنرد خلال 72 ساعة عمل.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @include('footer')
</body>
</html>
