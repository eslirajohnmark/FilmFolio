<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiaryEntry extends Model
{
    protected $fillable = [
        'user_id', 'film_id', 'watched_on',
        'rating', 'review', 'rewatch', 'liked',
    ];

    protected $casts = [
        'watched_on' => 'date',
        'rating' => 'float',
        'rewatch' => 'boolean',
        'liked' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function film()
    {
        return $this->belongsTo(Film::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
