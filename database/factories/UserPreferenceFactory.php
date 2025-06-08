<?php

namespace Database\Factories;

use App\Models\UserPreference;
use App\Models\User;
use App\Models\NewsCategory;
use App\Models\NewsAuthor;
use App\Models\NewsSource;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserPreferenceFactory extends Factory
{
    protected $model = UserPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'news_category_id' => NewsCategory::factory(),
            'news_author_id' => NewsAuthor::factory(),
            'news_source_id' => NewsSource::factory(),
        ];
    }
}
