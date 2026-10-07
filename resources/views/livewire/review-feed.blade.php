<div>
    <div class="section-label">{{ $heading }} <a href="{{ route('journal.index') }}">Journal →</a></div>

    <div class="review-grid">
        @forelse($reviews as $review)
            @include('partials.review-card', ['review' => $review])
        @empty
            <p class="empty-state">No reviews yet.</p>
        @endforelse
    </div>
</div>
