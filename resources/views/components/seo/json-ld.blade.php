@props(['schema'])

@php
    // UNESCAPED_UNICODE keeps Arabic readable; UNESCAPED_SLASHES keeps URLs clean.
    $encoded = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
<script type="application/ld+json">{!! $encoded !!}</script>
