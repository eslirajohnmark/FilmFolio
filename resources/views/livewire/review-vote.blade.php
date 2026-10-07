<div class="vote-group" role="group" aria-label="Agree or disagree with this review">
    <button type="button" wire:click="agree" class="vote-btn {{ $vote === 1 ? 'on agree' : '' }}"
            title="{{ $vote === 1 ? 'Remove agree' : 'Agree' }}" aria-pressed="{{ $vote === 1 ? 'true' : 'false' }}">
        <span aria-hidden="true">👍</span> Agree <b>{{ number_format($agrees) }}</b>
    </button>
    <button type="button" wire:click="disagree" class="vote-btn {{ $vote === -1 ? 'on disagree' : '' }}"
            title="{{ $vote === -1 ? 'Remove disagree' : 'Disagree' }}" aria-pressed="{{ $vote === -1 ? 'true' : 'false' }}">
        <span aria-hidden="true">👎</span> Disagree <b>{{ number_format($disagrees) }}</b>
    </button>
</div>
