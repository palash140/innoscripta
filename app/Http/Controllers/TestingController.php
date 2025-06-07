<?php

namespace App\Http\Controllers;

use App\Services\NewsProviders\GuardianProvider;
use App\Services\NewsProviders\NewsAPIProvider;
use App\Services\NewsProviders\NYTimesProvider;
use Illuminate\Http\Request;

class TestingController extends Controller
{
    public function index(NYTimesProvider $newsapi)
    {
        return $newsapi->fetchNews();
        // return config('news_providers.newsapi.key');
    }
}


/*
Usage Examples:

# Seed categories (proper Laravel way)
php artisan db:seed --class=NewsCategorySeeder

# Or seed everything
php artisan db:seed

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Category management commands
php artisan news:categories list
php artisan news:categories create --name="Cryptocurrency" --color="#F59E0B"
php artisan news:categories alias --name="Technology" --alias="blockchain"
php artisan news:categories stats
php artisan news:categories find --search="tech"

Benefits of this approach:

1. ✅ Follows Laravel conventions (seeders in database/seeders/)
2. ✅ Separates concerns (seeding vs management)
3. ✅ Easier to run in production (db:seed)
4. ✅ Can be run multiple times safely
5. ✅ Integrates with migrate:fresh --seed
6. ✅ Cleaner command focused on management only
7. ✅ Better for CI/CD pipelines
8. ✅ Version controlled with migrations

Production workflow:
1. php artisan migrate          # Run migrations
2. php artisan db:seed         # Seed categories
3. php artisan news:sync       # Start syncing news
*/
