@extends('counter.layout')

@section('title', 'طلب جديد')

@section('content')
    {{-- Confirmation: the assigned number, BIG, so staff writes it on the bag --}}
    @if (session('new_order'))
        <div class="card border-success shadow-sm rounded-4 mb-4 text-center">
            <div class="card-body py-4">
                <div class="fs-4 text-muted mb-1">تم تسجيل طلب {{ session('new_order')['customer_name'] }}</div>
                <div class="text-success fw-bold" style="font-size: 5rem; line-height: 1.1;">
                    رقم الطلب: {{ session('new_order')['id'] }}
                </div>
                @if (session('new_order')['shelf'])
                    <div class="fs-3 mt-1">الرف: <strong>{{ session('new_order')['shelf'] }}</strong></div>
                @endif
                <div class="text-muted fs-5 mt-2">✍️ اكتب هذا الرقم على الكيس</div>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger fs-5 rounded-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm rounded-4">
        <div class="card-body p-4">
            <h1 class="fs-3 fw-bold mb-4">➕ تسجيل طلب جديد</h1>

            <form method="POST" action="{{ route('counter.store') }}">
                @csrf

                <div class="mb-4">
                    <label for="customer_name" class="form-label fs-5 fw-bold">اسم الزبون *</label>
                    <input type="text" name="customer_name" id="customer_name"
                           class="form-control form-control-lg rounded-3" style="font-size: 1.4rem;"
                           value="{{ old('customer_name') }}" required autofocus autocomplete="off">
                </div>

                <div class="mb-4">
                    <label for="phone" class="form-label fs-5 fw-bold">رقم الهاتف *</label>
                    {{-- inputmode=numeric opens the numeric keypad on the tablet --}}
                    <input type="tel" name="phone" id="phone" inputmode="numeric"
                           class="form-control form-control-lg rounded-3" style="font-size: 1.4rem;" dir="ltr"
                           value="{{ old('phone') }}" required autocomplete="off">
                </div>

                <div class="mb-4">
                    <label for="shelf" class="form-label fs-5 fw-bold">الرف <span class="text-muted fs-6">(اختياري — مثلا: A أو 1-50)</span></label>
                    <input type="text" name="shelf" id="shelf"
                           class="form-control form-control-lg rounded-3" style="font-size: 1.4rem;"
                           value="{{ old('shelf') }}" autocomplete="off">
                </div>

                <button type="submit" class="btn btn-primary btn-xl w-100">💾 حفظ وإعطاء رقم</button>
            </form>
        </div>
    </div>
@endsection
