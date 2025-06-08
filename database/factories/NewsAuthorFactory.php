<?php

namespace Database\Factories;

use App\Models\NewsAuthor;
use Illuminate\Database\Eloquent\Factories\Factory;

class NewsAuthorFactory extends Factory
{
    protected $model = NewsAuthor::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
