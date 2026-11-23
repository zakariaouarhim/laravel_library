@props(['schema'])

@php
    // UNESCAPED_UNICODE keeps Arabic readable; UNESCAPED_SLASHES keeps URLs clean.
    // HEX_TAG escapes < and >: a user-supplied value reaches this (review
    // author names, via SchemaBuilder) and a display name containing
    // "</script>" would otherwise close the tag early.
    $encoded = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
@endphp
<script type="application/ld+json">{!! $encoded !!}</script>
