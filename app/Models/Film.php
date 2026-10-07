<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Film extends Model
{
    use HasFactory;

    /**
     * Folder on the public disk for uploaded posters, backdrops and videos.
     */
    public const UPLOAD_DIR = 'films';

    /**
     * Columns that may hold an uploaded file.
     *
     * @var list<string>
     */
    public const MEDIA_COLUMNS = ['poster_path', 'backdrop_path', 'trailer_path', 'teaser_path'];

    protected $fillable = [
        'title', 'year', 'genre', 'synopsis',
        'poster_path', 'backdrop_path', 'trailer_key', 'trailer_path', 'teaser_path', 'tmdb_id',
        'is_teaser', 'release_date',
    ];

    protected $casts = [
        'release_date' => 'date',
        'is_teaser' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleted(function (Film $film) {
            foreach (self::MEDIA_COLUMNS as $column) {
                if (self::isUpload($film->{$column})) {
                    Storage::disk('public')->delete($film->{$column});
                }
            }
        });
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function diaryEntries()
    {
        return $this->hasMany(DiaryEntry::class);
    }

    public function lists()
    {
        return $this->belongsToMany(FilmList::class, 'list_items');
    }

    /**
     * Members with a watched/liked state for this film.
     */
    public function members()
    {
        return $this->belongsToMany(User::class)->withPivot(['watched', 'liked'])->withTimestamps();
    }

    public function watchers()
    {
        return $this->members()->wherePivot('watched', true);
    }

    public function likers()
    {
        return $this->members()->wherePivot('liked', true);
    }

    /**
     * Order by how many members logged or rated the film in the last week.
     */
    public function scopePopularThisWeek(Builder $query): Builder
    {
        $since = now()->subWeek();

        return $query
            ->withCount([
                'diaryEntries as recent_logs_count' => fn ($q) => $q->where('created_at', '>=', $since),
                'reviews as recent_reviews_count' => fn ($q) => $q->where('created_at', '>=', $since),
            ])
            ->orderByDesc('recent_logs_count')
            ->orderByDesc('recent_reviews_count')
            ->latest('id');
    }

    public function avgRating(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    public function posterUrl(): string
    {
        return $this->imageUrl($this->poster_path);
    }

    public function backdropUrl(): string
    {
        return $this->imageUrl($this->backdrop_path);
    }

    public function trailerUrl(): ?string
    {
        return $this->trailer_key
            ? 'https://www.youtube.com/watch?v='.urlencode($this->trailer_key)
            : null;
    }

    public function trailerEmbedUrl(): ?string
    {
        return $this->trailer_key
            ? 'https://www.youtube-nocookie.com/embed/'.rawurlencode($this->trailer_key).'?rel=0'
            : null;
    }

    /**
     * Uploaded trailer video file, if any.
     */
    public function trailerVideoUrl(): ?string
    {
        return $this->trailer_path ? asset('storage/'.$this->trailer_path) : null;
    }

    /**
     * Uploaded teaser video file, if any.
     */
    public function teaserVideoUrl(): ?string
    {
        return $this->teaser_path ? asset('storage/'.$this->teaser_path) : null;
    }

    public function hasTrailer(): bool
    {
        return (bool) ($this->trailer_key || $this->trailer_path || $this->teaser_path);
    }

    /**
     * Whether a stored image/video path is a file uploaded to FilmFolio (vs a URL or TMDB path).
     */
    public static function isUpload(?string $path): bool
    {
        return $path !== null && str_starts_with($path, self::UPLOAD_DIR.'/');
    }

    /**
     * Resolve a poster/backdrop value: an uploaded file, a full URL, or a TMDB path.
     */
    protected function imageUrl(?string $path): string
    {
        return match (true) {
            ! $path => '',
            self::isUpload($path) => asset('storage/'.$path),
            str_starts_with($path, 'http') => $path,
            default => config('services.tmdb.image_base').$path,
        };
    }
}
