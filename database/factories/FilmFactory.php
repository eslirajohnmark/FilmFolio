<?php

namespace Database\Factories;

use App\Models\Film;
use Illuminate\Database\Eloquent\Factories\Factory;

class FilmFactory extends Factory
{
    protected $model = Film::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->unique()->sentence(3),
            'year' => $this->faker->numberBetween(1970, 2024),
            'genre' => $this->faker->randomElement([
                'Sci-Fi', 'Drama', 'Horror', 'Documentary', 'Comedy',
            ]),
            'synopsis' => $this->faker->paragraph(),
        ];
    }
}
