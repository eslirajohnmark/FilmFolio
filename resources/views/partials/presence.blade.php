{{--
    $member   User. A green dot and "Online", or a grey dot and how long ago they were last active.
              The "… ago" text keeps counting up in the browser (see partials/scripts).
--}}
@if($member->isOnline())
    <span class="presence is-online" title="Online now"><span class="presence-dot" aria-hidden="true"></span>Online</span>
@elseif($member->last_seen_at)
    <span class="presence" title="Last active {{ $member->last_seen_at->format('M d, Y g:i A') }}">
        <span class="presence-dot" aria-hidden="true"></span>Offline ·
        <span data-seen-at="{{ $member->last_seen_at->getTimestampMs() }}" data-rendered-at="{{ now()->getTimestampMs() }}">{{ $member->last_seen_at->diffForHumans() }}</span>
    </span>
@else
    <span class="presence" title="Hasn't been active since online tracking started"><span class="presence-dot" aria-hidden="true"></span>Not seen yet</span>
@endif
