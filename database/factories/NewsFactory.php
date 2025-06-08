<?php

namespace Database\Factories;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsAuthor;
use App\Models\NewsSource;
use Illuminate\Database\Eloquent\Factories\Factory;

class NewsFactory extends Factory
{
    protected $model = News::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraphs(3, true),
            'published_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'unique_id' => $this->faker->uuid(), // Remove unique() to avoid overflow
            'provider' => $this->faker->randomElement([
                'newsapi', 'guardian', 'nytimes', 'techcrunch', 'bbc'
            ]),
            'news_category_id' => NewsCategory::factory(),
            'news_author_id' => NewsAuthor::factory(),
            'news_source_id' => NewsSource::factory(),
        ];
    }
}
