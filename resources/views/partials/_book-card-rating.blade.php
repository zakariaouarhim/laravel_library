@if(($book->reviews_count ?? 0) > 0)
<div class="book-card-rating">
    @php $avgRating = round($book->reviews_avg_rating ?? 0, 1); @endphp
    @for($i = 1; $i <= 5; $i++)
        @if($i <= floor($avgRating))
            <i class="fas fa-star"></i>
        @elseif($i - $avgRating < 1 && $i - $avgRating > 0)
            <i class="fas fa-star-half-alt"></i>
        @else
            <i class="far fa-star"></i>
        @endif
    @endfor
    <span class="rating-count">({{ $book->reviews_count }})</span>
</div>
@endif
