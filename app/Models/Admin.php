<?php

namespace App\Models;

use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A FilmFolio administrator. Kept apart from members (`users`) and signed in
 * through the separate `admin` guard.
 */
class Admin extends Authenticatable
{
    /** @use HasFactory<AdminFactory> */
    use HasFactory;

    protected $fillable = ['name', 'username', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Official "Crew Pick" lists this admin created.
     */
    public function filmLists(): HasMany
    {
        return $this->hasMany(FilmList::class);
    }

    public function isAdmin(): bool
    {
        return true;
    }

    public function avatarUrl(): string
    {
        return asset('images/default-avatar.svg');
    }
}
