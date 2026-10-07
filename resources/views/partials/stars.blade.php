@php
    $value = (float) ($rating ?? 0);
    $full = (int) floor($value);
    $half = fmod($value, 1) >= 0.5;
@endphp
@if($value > 0)<span class="stars" title="{{ $value }} stars">{{ str_repeat('★', $full) }}{{ $half ? '½' : '' }}<span class="empty">{{ str_repeat('☆', max(0, 5 - $full - ($half ? 1 : 0))) }}</span></span>@endif
