{{--
    $suggestions  array{films, genres}|null  from App\Services\Recommendations::suggestionsFor()
    $style        poster-card style: 'tile' (default) or 'plate'
--}}
@if($suggestions)
    <section class="suggestions">
        <div class="section-label">
            <span>Suggested for you</span>
            <a href="{{ route('films.index', ['genre' => $suggestions['genres'][0]]) }}">More {{ $suggestions['genres'][0] }} →</a>
        </div>
        <div class="poster-row suggestions-row">
            @foreach($suggestions['films'] as $film)
                @include('partials.poster-card', ['film' => $film, 'style' => $style ?? 'tile'])
            @endforeach
        </div>
    </section>
@endif
