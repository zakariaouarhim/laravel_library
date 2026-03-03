{{-- Reusable book filter sidebar --}}
{{-- Required: $filterAction (form action URL) --}}
{{-- Optional: $publishingHouses (collection, shows publishers filter when set) --}}
{{-- Optional: $hiddenFields (array of name=>value pairs to preserve in form) --}}

<div class="sidebar-card">
    <div class="sidebar-card-header">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>تصفية النتائج</h5>
    </div>
    <div class="sidebar-card-body">
        <form method="GET" action="{{ $filterAction }}">
            @if(isset($hiddenFields))
                @foreach($hiddenFields as $name => $value)
                    @if($value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endif
                @endforeach
            @endif

            @if(isset($publishingHouses) && $publishingHouses->count() > 0)
            <!-- Publishers Filter -->
            <div class="filter-section">
                <h6 class="filter-title">دار النشر</h6>
                <input type="text" id="publisherSearch" class="form-control mb-3" placeholder="ابحث عن دار النشر...">

                <div id="publisherList">
                    @foreach ($publishingHouses as $index => $publishingHouse)
                        <div class="custom-checkbox {{ $index >= 4 ? 'd-none extra-publisher' : '' }}">
                            <input class="custom-checkbox-input"
                                type="checkbox"
                                name="publishers[]"
                                value="{{ $publishingHouse->id }}"
                                id="publisher{{ $publishingHouse->id }}"
                                {{ in_array($publishingHouse->id, request()->get('publishers', [])) ? 'checked' : '' }}>
                            <label class="custom-checkbox-label" for="publisher{{ $publishingHouse->id }}">
                                {{ $publishingHouse->name }}
                            </label>
                        </div>
                    @endforeach
                </div>

                @if ($publishingHouses->count() > 4)
                    <button type="button" id="showMoreBtn" class="btn btn-link mt-2 p-0" style="font-size: 0.9rem;">
                        <i class="fas fa-chevron-down me-1"></i> عرض المزيد
                    </button>
                @endif
            </div>
            @endif

            <!-- Language Filter -->
            <div class="filter-section">
                <h6 class="filter-title">اللغة</h6>
                <select class="form-select custom-select" name="language">
                    <option value="">جميع اللغات</option>
                    @foreach(App\Models\Book::LANGUAGES as $lang)
                        <option value="{{ $lang }}" {{ request('language') == $lang ? 'selected' : '' }}>
                            {{ App\Models\Book::LANGUAGE_LABELS[$lang] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Price Range Filter -->
            <div class="filter-section">
                <h6 class="filter-title">نطاق السعر</h6>
                <div class="price-range">
                    <div class="range-inputs mt-3">
                        <div class="input-group">
                            <span class="input-group-text">من</span>
                            <input type="number" class="form-control" name="price_min"
                                placeholder="0" value="{{ request('price_min') }}">
                            <span class="input-group-text">د.م</span>
                        </div>
                        <div class="input-group mt-2">
                            <span class="input-group-text">إلى</span>
                            <input type="number" class="form-control" name="price_max"
                                placeholder="1000" value="{{ request('price_max') }}">
                            <span class="input-group-text">د.م</span>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-filter w-100">
                <i class="fas fa-filter me-2"></i>تطبيق الفلتر
            </button>
        </form>
    </div>
</div>
