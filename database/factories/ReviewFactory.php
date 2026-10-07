<?php

namespace Database\Factories;

use App\Models\Film;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'film_id' => Film::inRandomOrder()->first()?->id ?? Film::factory(),
            'rating' => $this->faker->randomElement([1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5]),
            'body' => $this->faker->sentence(12),

        ];
    }
}
