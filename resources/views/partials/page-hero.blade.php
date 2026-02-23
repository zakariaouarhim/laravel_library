{{--
    Unified page hero partial.

    Parameters:
      title       (string)  required — page heading
      subtitle    (string)  optional — line below the title
      icon        (string)  optional — Font Awesome class e.g. 'fas fa-shield-alt'
      breadcrumbs (array)   optional — each item: ['label'=>'', 'url'=>''] (last item has no 'url' = active)
      centered    (bool)    optional — center-align content, default true
      heroClass   (string)  optional — extra CSS class e.g. 'page-hero--accent'
--}}
<div class="page-hero {{ $heroClass ?? '' }}">
    <div class="container">
        <div class="hero-content {{ ($centered ?? true) ? 'text-center' : '' }}">
            <h1 class="hero-title">
                @if(!empty($icon))<i class="{{ $icon }} me-2"></i>@endif{{ $title }}
            </h1>
            @if(!empty($subtitle))
                <p class="hero-subtitle">{{ $subtitle }}</p>
            @endif
            @if(!empty($breadcrumbs))
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb {{ ($centered ?? true) ? 'justify-content-center' : '' }} mb-0">
                    @foreach($breadcrumbs as $crumb)
                        @if(isset($crumb['url']))
                            <li class="breadcrumb-item">
                                <a href="{{ $crumb['url'] }}">
                                    @if($loop->first)<i class="fas fa-home"></i> @endif{{ $crumb['label'] }}
                                </a>
                            </li>
                        @else
                            <li class="breadcrumb-item active" aria-current="page">{{ $crumb['label'] }}</li>
                        @endif
                    @endforeach
                </ol>
            </nav>
            @endif
        </div>
    </div>
</div>
