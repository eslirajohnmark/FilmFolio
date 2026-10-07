<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\DiaryEntry;
use App\Models\Film;
use App\Models\Review;
use App\Models\Tag;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class DiaryLogger extends Component
{
    public Film $film;

    public float $rating = 0;

    public string $review = '';

    public string $watched_on;

    public bool $rewatch = false;

    public bool $liked = false;

    public array $tags = [];

    public string $newTag = '';

    public bool $showForm = false;

    /**
     * Bumped on every reset so the child star-rating component re-mounts with the new value.
     */
    public int $formVersion = 0;

    public function mount(Film $film): void
    {
        $this->film = $film;
        $this->resetForm();
    }

    /**
     * Start a fresh log: today's date, the user's current film rating, and
     * "rewatch" pre-ticked when they have logged this film before.
     */
    protected function resetForm(): void
    {
        $this->watched_on = now()->format('Y-m-d');
        $this->review = '';
        $this->liked = false;
        $this->tags = [];
        $this->newTag = '';
        $this->rating = 0;
        $this->rewatch = false;

        if (auth()->check()) {
            $this->rating = (float) (Review::where('user_id', auth()->id())
                ->where('film_id', $this->film->id)
                ->value('rating') ?? 0);
            $this->rewatch = DiaryEntry::where('user_id', auth()->id())
                ->where('film_id', $this->film->id)
                ->exists();
        }

        $this->formVersion++;
    }

    #[On('rating-updated')]
    public function updateRating($rating, $model): void
    {
        if ($model === 'diary') {
            $this->rating = (float) $rating;
        }
    }

    public function addTag(): void
    {
        $name = trim($this->newTag);
        $this->newTag = '';
        $this->resetErrorBag('newTag');

        if (Str::slug($name) === '' || mb_strlen($name) > 40) {
            $this->addError('newTag', 'Tags need 1–40 characters including a letter or number.');

            return;
        }

        if (! in_array($name, $this->tags, true)) {
            $this->tags[] = $name;
        }
    }

    public function removeTag(int $index): void
    {
        unset($this->tags[$index]);
        $this->tags = array_values($this->tags);
    }

    public function save(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));

            return;
        }

        $this->validate([
            'rating' => 'nullable|numeric|min:0|max:5',
            'review' => 'nullable|string|max:5000',
            'watched_on' => 'required|date|before_or_equal:today',
            'tags.*' => 'string|max:40',
        ]);

        $entry = DiaryEntry::create([
            'user_id' => auth()->id(),
            'film_id' => $this->film->id,
            'watched_on' => $this->watched_on,
            'rating' => $this->rating ?: null,
            'review' => $this->review ?: null,
            'rewatch' => $this->rewatch,
            'liked' => $this->liked,
        ]);

        $tagIds = [];
        foreach ($this->tags as $name) {
            $tag = Tag::findOrCreate($name);
            $tag->incrementUse();
            $tagIds[] = $tag->id;
        }
        $entry->tags()->sync($tagIds);
        $this->film->tags()->syncWithoutDetaching($tagIds);

        auth()->user()->markWatched($this->film);
        if ($this->liked) {
            auth()->user()->films()->updateExistingPivot($this->film->id, ['liked' => true]);
        }

        Activity::log(
            auth()->user(),
            $this->review ? 'reviewed' : 'watched',
            $this->film,
            [
                'rating' => $this->rating,
                'body' => $this->review,
                'rewatch' => $this->rewatch,
                'diary_id' => $entry->id,
            ]
        );

        if ($this->rating > 0) {
            Review::updateOrCreate(
                ['user_id' => auth()->id(), 'film_id' => $this->film->id],
                array_filter(['rating' => $this->rating, 'body' => $this->review ?: null]),
            );
        }

        $this->dispatch('notify', message: 'Diary entry saved!');
        $this->showForm = false;
        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.diary-logger', [
            'entries' => DiaryEntry::with('tags')
                ->where('user_id', auth()->id())
                ->where('film_id', $this->film->id)
                ->latest('watched_on')
                ->get(),
        ]);
    }
}
