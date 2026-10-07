<div style="display:inline-block;position:relative;">
    <button class="btn-register" wire:click="$toggle('open')">+ Add to List</button>

    @if($open)
        <div class="diary-form" style="position:absolute;z-index:10;min-width:240px;margin-top:8px;padding:12px;">
            @forelse($lists as $list)
                <label class="checkbox" style="display:flex;gap:8px;align-items:center;padding:4px 0;cursor:pointer;">
                    <input type="checkbox"
                           wire:click="toggle({{ $list->id }})"
                           wire:key="atl-{{ $list->id }}"
                           @checked($list->has_film)>
                    {{ $list->name }}
                </label>
            @empty
                <p style="color:var(--text-dim);font-size:13px;">
                    You have no lists yet.
                    <a href="{{ route('lists.index') }}" style="color:var(--purple-glow);">Create one →</a>
                </p>
            @endforelse
        </div>
    @endif
</div>
