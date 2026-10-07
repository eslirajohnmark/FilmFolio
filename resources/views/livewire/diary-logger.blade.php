<div>
    @guest
        <p style="color:var(--text-dim);">
            <a href="{{ route('login') }}" style="color:var(--purple-glow);">Log in</a>
            to log this film.
        </p>
    @endguest

    @auth
        <div style="display:flex;gap:14px;align-items:center;margin-bottom:20px;">
            <livewire:star-rating :rating="$rating" model="diary" :key="'star-'.$film->id.'-'.$formVersion" />
            <button class="btn-register" wire:click="$toggle('showForm')">
                {{ $showForm ? 'Cancel' : '✎ Log a Watch' }}
            </button>
        </div>

        @if($showForm)
            <div class="diary-form">
                <div class="form-grid">
                    <label>Watched on
                        <input type="date" wire:model="watched_on">
                    </label>
                    <label class="checkbox">
                        <input type="checkbox" wire:model="rewatch"> Rewatch
                    </label>
                    <label class="checkbox">
                        <input type="checkbox" wire:model="liked"> ♥ Liked
                    </label>
                </div>

                <textarea wire:model="review"
                          placeholder="Write your review…"
                          style="min-height:120px;margin-top:12px;"></textarea>

                <div style="margin-top:12px;">
                    <div style="font-size:12px;color:var(--text-dim);margin-bottom:6px;">Tags</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
                        @foreach($tags as $i => $t)
                            <span class="tag-chip">
                                {{ $t }}
                                <button wire:click="removeTag({{ $i }})" class="tag-x">×</button>
                            </span>
                        @endforeach
                    </div>
                    <div style="display:flex;gap:8px;">
                        <input type="text"
                               wire:model="newTag"
                               wire:keydown.enter.prevent="addTag"
                               placeholder="Add a tag…"
                               style="flex:1;background:var(--bg-2);border:1px solid var(--border);color:#fff;padding:8px 12px;border-radius:6px;">
                        <button class="btn-register" wire:click="addTag">Add</button>
                    </div>
                    @error('newTag') <div class="error-list" style="margin-top:6px;">{{ $message }}</div> @enderror
                    @error('watched_on') <div class="error-list" style="margin-top:6px;">{{ $message }}</div> @enderror
                </div>

                <button class="btn-register" wire:click="save" wire:loading.attr="disabled" wire:target="save" style="margin-top:16px;">
                    Save Entry
                </button>
            </div>
        @endif

        @if($entries->isNotEmpty())
            <div style="margin-top:24px;">
                <h3 style="color:#fff;font-size:14px;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;">
                    Your Diary for This Film
                </h3>
                @foreach($entries as $entry)
                    <div class="diary-row">
                        <div>
                            <div style="color:#fff;font-weight:600;">
                                {{ $entry->watched_on->format('M d, Y') }}
                                @if($entry->rewatch)
                                    <span class="badge" style="font-size:10px;">Rewatch</span>
                                @endif
                                @if($entry->liked)
                                    <span style="color:var(--pink);">♥</span>
                                @endif
                            </div>
                            @if($entry->rating)
                                <div class="stars" style="color:var(--purple-glow);">
                                    @php
                                        $f = floor($entry->rating);
                                        $h = ($entry->rating - $f) >= 0.5;
                                    @endphp
                                    {{ str_repeat('★', (int) $f) }}{{ $h ? '½' : '' }}
                                </div>
                            @endif
                        </div>
                        <div style="color:var(--text-dim);font-size:13px;">
                            {{ $entry->review }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endauth
</div>