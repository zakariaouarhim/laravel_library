@extends('layouts.public')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/about.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .terms-body { padding: 60px 0; background: #f8f9fa; }
        .terms-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,.06);
            padding: 48px 56px;
            max-width: 860px;
            margin: 0 auto;
            font-family: 'Tajawal', sans-serif;
            line-height: 1.9;
            color: #333;
        }
        .terms-card h6 { color: #1a1a2e; }
        .terms-card ul { padding-right: 20px; }
        .terms-card li { margin-bottom: 6px; }
        @media (max-width: 768px) {
            .terms-card { padding: 28px 20px; }
        }
    </style>
@endpush

@section('content')
    <section class="terms-body">
        <div class="container">
            <div class="text-center mb-5">
                <h1 class="fw-bold" style="font-family:'Tajawal',sans-serif;">
                    <i class="fas fa-file-contract me-2 text-primary"></i>الشروط والأحكام
                </h1>
            </div>
            <div class="terms-card">
                @include('partials.terms-content')
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/header.js') }}" defer></script>
@endpush
