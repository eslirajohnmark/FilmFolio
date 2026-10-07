<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = ['name', 'slug', 'use_count'];

    public function films()
    {
        return $this->belongsToMany(Film::class);
    }

    public function diaries()
    {
        return $this->belongsToMany(DiaryEntry::class);
    }

    public static function findOrCreate(string $name): self
    {
        $slug = Str::slug($name);

        return static::firstOrCreate(
            ['slug' => $slug],
            ['name' => trim($name)]
        );
    }

    public function incrementUse(): void
    {
        $this->increment('use_count');
    }
}
