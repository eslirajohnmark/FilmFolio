{{--
    $film     Film
    $style    'tile' (rounded poster with title + year below, default) or 'plate' (Courier "Title (YEAR)" plate, home page)
    $user     ?User   show this member's avatar on hover
    $rating   ?float  show this rating on hover
--}}
@php $style = $style ?? 'tile'; @endphp
<a href="{{ route('films.show', $film) }}" class="poster-card {{ $style }}" title="{{ $film->title }} ({{ $film->year }})">
    <div class="poster" @if($film->posterUrl()) style="background-image:url('{{ $film->posterUrl() }}')" @endif>
        @unless($film->posterUrl())
            @include('partials.poster-placeholder', ['icon' => $style === 'plate' ? 'film' : 'play'])
        @endunless

        @if(isset($user) || ! empty($rating))
            <div class="poster-meta {{ isset($user) ? 'always' : '' }}">
                @isset($user)
                    <img src="{{ $user->avatarUrl() }}" alt="&#64;{{ $user->username ?? $user->name }}">
                @endisset
                @include('partials.stars', ['rating' => $rating ?? 0])
            </div>
        @endif
    </div>

    @if($style === 'plate')
        <div class="caption">{{ $film->title }}<span>({{ $film->year ?: 'TBA' }})</span></div>
    @else
        <div class="title">{{ $film->title }}</div>
        <div class="year">{{ $film->year ?: 'TBA' }}</div>
    @endif
</a>
