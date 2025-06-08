<?php

namespace Database\Factories;

use App\Models\NewsSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NewsSourceFactory extends Factory
{
    protected $model = NewsSource::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'TechCrunch', 'BBC News', 'CNN', 'Reuters',
            'The Guardian', 'Associated Press', 'Forbes',
            'Wired', 'Ars Technica', 'The Verge', 'Engadget',
            'Mashable', 'TechRadar', 'CNET', 'ZDNet'
        ]);

        return [
            'name' => $name,
            'domain' => $this->faker->domainName(),
            'provider' => $this->faker->randomElement([
                'newsapi', 'guardian', 'nytimes', 'techcrunch', 'bbc'
            ]),
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 9999),
        ];
    }
}
