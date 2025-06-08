<?php

namespace Database\Factories;

// database/factories/NewsCategoryFactory.php

namespace Database\Factories;

use App\Models\NewsCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NewsCategoryFactory extends Factory
{
    protected $model = NewsCategory::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Technology', 'Sports', 'Politics', 'Entertainment',
            'Business', 'Health', 'Science', 'World News',
            'Finance', 'Education', 'Travel', 'Food', 'Fashion'
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->uuid(),
        ];
    }
}
