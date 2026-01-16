<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تفاصيل الشحنة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
</head>
<body>
    
    
    <div class="container-fluid">
        <div class="row">
            @include('Dashbord_Admin.Sidebar')
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">تفاصيل الشحنة: {{ $shipment->shipment_reference }}</h1>
                    <a href="{{ route('Dashbord_Admin.Shipment_Management') }}" class="btn btn-secondary">
                        العودة إلى القائمة
                    </a>
                </div>
                
                <!-- Shipment Details -->
                <div class="card mb-4">
                    <div class="card-header">معلومات الشحنة</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>رقم الشحنة:</strong> {{ $shipment->shipment_reference }}</p>
                                <p><strong>المورد:</strong> {{ $shipment->supplier_name ?? 'غير محدد' }}</p>
                                <p><strong>تاريخ الوصول:</strong> {{ \Carbon\Carbon::parse($shipment->arrival_date)->format('Y-m-d') }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>إجمالي الكتب:</strong> {{ $shipment->total_books }}</p>
                                <p><strong>الكتب المعالجة:</strong> {{ $shipment->processed_books }}</p>
                                <p><strong>الحالة:</strong>
                                    <span class="badge bg-{{ $shipment->status == 'completed' ? 'success' : ($shipment->status == 'processing' ? 'warning' : 'secondary') }}">
                                        {{ $shipment->status == 'completed' ? 'مكتملة' : ($shipment->status == 'processing' ? 'قيد المعالجة' : 'في الانتظار') }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        @if($shipment->notes)
                            <p><strong>ملاحظات:</strong> {{ $shipment->notes }}</p>
                        @endif
                    </div>
                </div>
                
                <!-- Shipment Items -->
                <div class="card">
                    <div class="card-header">كتب الشحنة</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ISBN</th>
                                        <th>العنوان</th>
                                        <th>المؤلف</th>
                                        <th>الكمية</th>
                                        <th>سعر التكلفة</th>
                                        <th>سعر البيع</th>
                                        <th>حالة البيانات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($shipment->items as $item)
                                        <tr>
                                            <td>{{ $item->isbn }}</td>
                                            <td>{{ $item->title }}</td>
                                            <td>{{ $item->author ?? 'غير محدد' }}</td>
                                            <td>{{ $item->quantity_received }}</td>
                                            <td>{{ $item->cost_price ? number_format($item->cost_price, 2) : 'N/A' }}</td>
                                            <td>{{ number_format($item->selling_price, 2) }}</td>
                                            <td>
                                                @if($item->book && $item->book->api_data_status === 'enriched')
                                                    <span class="badge bg-success">مكتمل</span>
                                                @else
                                                    <span class="badge bg-warning">قيد الانتظار</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Processing Actions -->
                @if($shipment->status === 'processing')
                    <div class="mt-4">
                        <form method="POST" action="{{ route('shipments.bulk-enrich', $shipment->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                تجميع بيانات الكتب من API
                            </button>
                        </form>
                    </div>
                @endif
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>