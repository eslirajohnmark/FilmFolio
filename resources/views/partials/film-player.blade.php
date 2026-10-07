{{--
    $film    Film
    $prefer    'teaser' (Teasers page) or 'trailer' (film page)
    $autoplay  bool  start playing immediately (after the visitor pressed play)
    Plays an uploaded video first, then the YouTube trailer.
--}}
@php
    $videos = ($prefer ?? 'trailer') === 'teaser'
        ? [$film->teaserVideoUrl(), $film->trailerVideoUrl()]
        : [$film->trailerVideoUrl(), $film->teaserVideoUrl()];
    $videoUrl = collect($videos)->filter()->first();
    $autoplay = $autoplay ?? false;
@endphp
<div class="teaser-player">
    @if($videoUrl)
        <video src="{{ $videoUrl }}" controls preload="metadata" playsinline @if($autoplay) autoplay @endif
               @if($film->backdropUrl()) poster="{{ $film->backdropUrl() }}" @endif
               aria-label="{{ $film->title }} {{ ($prefer ?? 'trailer') }}"></video>
    @elseif($film->trailerEmbedUrl())
        <iframe src="{{ $film->trailerEmbedUrl() }}{{ $autoplay ? '&autoplay=1' : '' }}"
                title="{{ $film->title }} trailer"
                allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                referrerpolicy="strict-origin-when-cross-origin"
                loading="lazy" allowfullscreen></iframe>
    @else
        <div class="teaser-noplayer" @if($film->backdropUrl()) style="background-image:url('{{ $film->backdropUrl() }}')" @endif>
            <span>No trailer yet</span>
        </div>
    @endif
</div>
