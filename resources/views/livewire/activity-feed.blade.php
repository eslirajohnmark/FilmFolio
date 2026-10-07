<div>
    <div class="tabs" style="margin-bottom:24px;">
        <a wire:click.prevent="$set('scope','following')"
           class="{{ $scope === 'following' ? 'active' : '' }}"
           style="cursor:pointer;">Following</a>
        <a wire:click.prevent="$set('scope','global')"
           class="{{ $scope === 'global' ? 'active' : '' }}"
           style="cursor:pointer;">Everyone</a>
    </div>

    @forelse($activities as $activity)
        @include('partials.activity-row', ['activity' => $activity])
    @empty
        <p style="color:var(--text-dim);padding:20px 0;">
            Nothing here yet. Follow some members to fill your feed!
        </p>
    @endforelse

    <div style="margin-top:20px;">{{ $activities->links() }}</div>
</div>