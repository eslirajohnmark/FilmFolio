<?php

namespace App\Livewire;

use App\Models\Film;
use App\Services\Recommendations;
use Livewire\Component;
use Livewire\WithPagination;

class FilmSearch extends Component
{
    use WithPagination;

    public string $q = '';

    public string $genre = '';

    public string $decade = '';

    public string $sort = 'popular';

    public int $perPage = 18;

    protected $queryString = [
        'q' => ['except' => ''],
        'genre' => ['except' => ''],
        'decade' => ['except' => ''],
        'sort' => ['except' => 'popular'],
    ];

    /**
     * @var array<string, string>
     */
    public const SORTS = [
        'popular' => 'Most popular',
        'rating' => 'Highest rated',
        'newest' => 'Newest releases',
        'oldest' => 'Oldest releases',
        'added' => 'Recently added',
        'title' => 'Title A–Z',
    ];

    public function updating(): void
    {
        $this->resetPage();
    }

    /**
     * Typing a genre ("horror", "scary"…) into the filter counts towards suggestions.
     */
    public function updatedQ(): void
    {
        app(Recommendations::class)->recordSearch($this->q);
    }

    public function updatedGenre(): void
    {
        if ($this->genre !== '') {
            app(Recommendations::class)->recordFilter($this->genre);
        }
    }

    public function render()
    {
        $films = Film::query()
            ->when(trim($this->q) !== '', fn ($query) => $query->where(fn ($match) => $match
                ->where('title', 'like', '%'.trim($this->q).'%')
                ->orWhere('genre', 'like', '%'.trim($this->q).'%')
                ->when(ctype_digit(trim($this->q)), fn ($byYear) => $byYear->orWhere('year', (int) trim($this->q)))))
            ->when($this->genre, fn ($query) => $query->where('genre', $this->genre))
            ->when($this->decade, fn ($query) => $query->whereBetween('year', [(int) $this->decade, (int) $this->decade + 9]))
            ->withCount(['watchers', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->tap(fn ($query) => match ($this->sort) {
                'rating' => $query->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count'),
                'newest' => $query->orderByDesc('year')->orderByDesc('release_date'),
                'oldest' => $query->orderBy('year')->orderBy('release_date'),
                'added' => $query->latest(),
                'title' => $query->orderBy('title'),
                default => $query->orderByDesc('watchers_count')->orderByDesc('reviews_count')->latest(),
            })
            ->paginate($this->perPage);

        return view('livewire.film-search', [
            'films' => $films,
            'sorts' => self::SORTS,
            'genres' => Film::query()->whereNotNull('genre')->distinct()->orderBy('genre')->pluck('genre'),
            'decades' => Film::query()
                ->selectRaw('DISTINCT year - (year % 10) AS decade')
                ->orderByDesc('decade')
                ->pluck('decade')
                ->map(fn ($decade) => (int) $decade),
        ]);
    }
}
