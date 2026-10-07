<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Watchlist extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'film_id'];

    public function film()
    {
        return $this->belongsTo(Film::class);
    }
}
