<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إحصائيات البحث</title>

    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .filter-bar {
            background: #fff;
            padding: 1rem 1.25rem;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-bar label { font-weight: 600; font-size: .85rem; color: var(--color-text-dark, #1a2f4e); }
        .filter-bar select {
            border-radius: 10px;
            border: 1.5px solid var(--color-border, #e0e0e0);
            padding: .4rem .65rem;
            font-family: 'Cairo', sans-serif;
            font-size: .9rem;
        }
        .si-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 1.5rem; }
        .si-stat-card {
            background: #fff; border-radius: 14px; padding: 1.25rem 1.5rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        .si-stat-label { font-size: .85rem; color: var(--color-text-muted, #888); margin-bottom: .35rem; }
        .si-stat-value { font-size: 2rem; font-weight: 700; color: var(--color-primary-dark, #203a61); }
        .si-stat-value.warn { color: var(--color-danger, #dc3545); }
        .si-card {
            background: #fff; border-radius: 14px; padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 1.5rem;
        }
        .si-card h4 {
            font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem;
            color: var(--color-primary-dark, #203a61);
            display: flex; align-items: center; gap: .5rem;
        }
        .si-card h4 .badge { font-size: .7rem; }
        .si-table { width: 100%; border-collapse: collapse; }
        .si-table th, .si-table td {
            padding: .65rem .75rem; text-align: start;
            border-bottom: 1px solid var(--color-border-light, #f0f0f0);
        }
        .si-table th { font-size: .8rem; color: var(--color-text-muted); text-transform: uppercase; font-weight: 600; }
        .si-table td.num { text-align: center; }
        .si-table tr:hover { background: var(--color-accent-bg, #f0f4fa); }
        .si-query { font-weight: 600; color: var(--color-text-dark, #1a2f4e); }
        .si-norm { font-size: .8rem; color: var(--color-text-muted); display: block; }
        .si-empty { text-align: center; padding: 2rem; color: var(--color-text-muted); }
        .si-help {
            background: var(--color-accent-bg, #f0f4fa);
            border-right: 4px solid var(--color-accent, #5A84C3);
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            color: var(--color-text, #333);
        }
        @media (max-width: 768px) {
            .si-stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="dashboard_layout">
    <div class="container-fluid">
        <div class="row">
            @include('Dashbord_Admin.Sidebar')

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="dashboard-header">
                    <h1>
                        <i class="fas fa-search"></i>
                        إحصائيات البحث
                    </h1>
                </div>

                <div class="si-help">
                    <strong>كيف تستخدم هذه الصفحة:</strong>
                    استعرض أكثر العبارات التي يبحث عنها الزوار، واستخدمها كمفردات في المحتوى التحريري لأقسام التصنيفات.
                    أما عبارات «صفر نتائج» فهي مؤشرات على فجوات في المخزون أو تباين في التسمية.
                </div>

                {{-- Filters --}}
                <form method="GET" class="filter-bar">
                    <label for="window">الفترة:</label>
                    <select name="window" id="window" onchange="this.form.submit()">
                        <option value="7"   {{ $windowDays === 7   ? 'selected' : '' }}>آخر 7 أيام</option>
                        <option value="30"  {{ $windowDays === 30  ? 'selected' : '' }}>آخر 30 يوم</option>
                        <option value="90"  {{ $windowDays === 90  ? 'selected' : '' }}>آخر 90 يوم</option>
                        <option value="365" {{ $windowDays === 365 ? 'selected' : '' }}>آخر سنة</option>
                    </select>

                    <label for="source">المصدر:</label>
                    <select name="source" id="source" onchange="this.form.submit()">
                        <option value="all"          {{ $source === 'all'          ? 'selected' : '' }}>الكل</option>
                        <option value="page"         {{ $source === 'page'         ? 'selected' : '' }}>صفحة البحث</option>
                        <option value="autocomplete" {{ $source === 'autocomplete' ? 'selected' : '' }}>الإكمال التلقائي</option>
                    </select>
                </form>

                {{-- Totals --}}
                <div class="si-stats">
                    <div class="si-stat-card">
                        <div class="si-stat-label">إجمالي عمليات البحث</div>
                        <div class="si-stat-value">{{ number_format($totals['all_searches']) }}</div>
                    </div>
                    <div class="si-stat-card">
                        <div class="si-stat-label">عبارات بحث فريدة</div>
                        <div class="si-stat-value">{{ number_format($totals['unique_queries']) }}</div>
                    </div>
                    <div class="si-stat-card">
                        <div class="si-stat-label">نسبة «صفر نتائج»</div>
                        <div class="si-stat-value {{ $totals['zero_result_pct'] >= 20 ? 'warn' : '' }}">
                            {{ $totals['zero_result_pct'] }}%
                        </div>
                    </div>
                </div>

                {{-- Top queries --}}
                <div class="si-card">
                    <h4>
                        <i class="fas fa-fire text-warning"></i>
                        أكثر عبارات البحث تكراراً
                        <span class="badge bg-light text-dark">{{ $topQueries->count() }}</span>
                    </h4>
                    @if($topQueries->isEmpty())
                        <div class="si-empty">لا توجد بيانات بعد لهذه الفترة.</div>
                    @else
                        <table class="si-table">
                            <thead>
                                <tr>
                                    <th>العبارة</th>
                                    <th class="num">عدد المرات</th>
                                    <th class="num">متوسط النتائج</th>
                                    <th class="num">آخر بحث</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($topQueries as $row)
                                <tr>
                                    <td>
                                        <span class="si-query">{{ $row->sample_query }}</span>
                                        @if($row->sample_query !== $row->normalized_query)
                                            <span class="si-norm">{{ $row->normalized_query }}</span>
                                        @endif
                                    </td>
                                    <td class="num"><strong>{{ number_format($row->hits) }}</strong></td>
                                    <td class="num">{{ number_format((float) $row->avg_results, 1) }}</td>
                                    <td class="num">{{ \Carbon\Carbon::parse($row->last_seen)->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                {{-- Zero-result queries --}}
                <div class="si-card">
                    <h4>
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                        عبارات بحث بلا نتائج
                        <span class="badge bg-light text-dark">{{ $zeroResultQueries->count() }}</span>
                    </h4>
                    <p class="text-muted small mb-3">
                        فجوات محتملة في المخزون أو فروق في الكتابة (مثل «نجيب محفوظ» بدون شكل أو بشكل مختلف).
                    </p>
                    @if($zeroResultQueries->isEmpty())
                        <div class="si-empty">لا توجد عبارات بحث بلا نتائج في هذه الفترة. 🎉</div>
                    @else
                        <table class="si-table">
                            <thead>
                                <tr>
                                    <th>العبارة</th>
                                    <th class="num">عدد المحاولات</th>
                                    <th class="num">آخر بحث</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($zeroResultQueries as $row)
                                <tr>
                                    <td>
                                        <span class="si-query">{{ $row->sample_query }}</span>
                                        @if($row->sample_query !== $row->normalized_query)
                                            <span class="si-norm">{{ $row->normalized_query }}</span>
                                        @endif
                                    </td>
                                    <td class="num"><strong>{{ number_format($row->hits) }}</strong></td>
                                    <td class="num">{{ \Carbon\Carbon::parse($row->last_seen)->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

            </main>
        </div>
    </div>
</div>

</body>
</html>
