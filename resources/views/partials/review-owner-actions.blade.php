{{-- Edit (author) and delete (author, or a signed-in admin) controls for a review or rating. --}}
@can('update', $review)
    <a href="{{ route('films.show', $review->film_id) }}#your-review" class="link" style="font-size:12px;">Edit</a>
@endcan
@can('delete', $review)
    <form method="POST" action="{{ route('reviews.destroy', $review) }}" style="display:inline;"
          onsubmit="return confirm('{{ $review->body ? 'Delete this review?' : 'Remove this rating?' }}')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn-ghost" style="font-size:12px;">Delete</button>
    </form>
@elseif(auth('admin')->check())
    <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" style="display:inline;"
          onsubmit="return confirm('{{ $review->body ? 'Remove this review? (admin)' : 'Remove this rating? (admin)' }}')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn-ghost" style="font-size:12px;">Remove (admin)</button>
    </form>
@endcan
