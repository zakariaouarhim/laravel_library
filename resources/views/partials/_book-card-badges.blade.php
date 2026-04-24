<div class="card-badges">
    @if($bundledOnly)
        <span class="badge badge-bundle-only"><i class="fas fa-box"></i> متوفر كباقة</span>
    @elseif($outOfStock)
        <span class="badge out-of-stock-badge">نفذ المخزون</span>
    @else
        @if($book->is_new ?? false)
            <span class="badge bg-success">جديد</span>
        @endif
        @if(($book->discount ?? 0) > 0)
            <span class="badge bg-danger">خصم {{ $book->discount }}%</span>
        @endif
        @if($inBundle && $firstBundle)
            <a href="{{ route('moredetail2.page', $firstBundle->id) }}" class="badge badge-bundle-hint">
                <i class="fas fa-box"></i> متوفر أيضاً كباقة
            </a>
        @endif
    @endif
    @if(($book->author_id && in_array($book->author_id, $followedAuthorIds ?? [])) || ($book->publishing_house_id && in_array($book->publishing_house_id, $followedPublisherIds ?? [])))
        <span class="badge badge-followed"><i class="fas fa-user-check"></i> متابَع</span>
    @endif
</div>
