<?php

namespace Database\Seeders;

use App\Models\NewsCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NewsCategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🗂️  Seeding news categories with comprehensive aliases...');

        $defaultCategories = [
            [
                'name' => 'Technology',
                'description' => 'Technology and innovation news',
                'color' => '#3B82F6',
                'sort_order' => 1,
                'aliases' => [
                    // NewsAPI
                    'technology',
                    // Guardian
                    'technology', 'tech',
                    // NYTimes
                    'technology', 'automobiles',
                    // Common variations
                    'sci-tech', 'computing', 'digital', 'ai', 'artificial-intelligence'
                ]
            ],
            [
                'name' => 'Business',
                'description' => 'Business and economic news',
                'color' => '#10B981',
                'sort_order' => 2,
                'aliases' => [
                    // All providers
                    'business',
                    // NYTimes
                    'realestate',
                    // Guardian
                    'money',
                    // Common variations
                    'economy', 'finance', 'financial', 'markets', 'economics'
                ]
            ],
            [
                'name' => 'Sports',
                'description' => 'Sports and athletics news',
                'color' => '#F59E0B',
                'sort_order' => 3,
                'aliases' => [
                    // NewsAPI & NYTimes
                    'sports',
                    // Guardian
                    'sport',
                    // Common variations
                    'athletics', 'games'
                ]
            ],
            [
                'name' => 'Entertainment',
                'description' => 'Entertainment and celebrity news',
                'color' => '#EC4899',
                'sort_order' => 4,
                'aliases' => [
                    // NewsAPI
                    'entertainment',
                    // Guardian
                    'culture', 'film', 'music', 'books', 'artanddesign',
                    // NYTimes
                    'arts', 'movies', 'theater', 'fashion',
                    // Common variations
                    'celebrity', 'multimedia'
                ]
            ],
            [
                'name' => 'Health',
                'description' => 'Health and medical news',
                'color' => '#EF4444',
                'sort_order' => 5,
                'aliases' => [
                    // All providers
                    'health',
                    // NYTimes
                    'well',
                    // Common variations
                    'medical', 'medicine', 'wellness', 'healthcare'
                ]
            ],
            [
                'name' => 'Science',
                'description' => 'Science and research news',
                'color' => '#8B5CF6',
                'sort_order' => 6,
                'aliases' => [
                    // All providers
                    'science',
                    // Guardian
                    'environment',
                    // NYTimes
                    'climate',
                    // Common variations
                    'research', 'nature', 'space'
                ]
            ],
            [
                'name' => 'Politics',
                'description' => 'Political news and analysis',
                'color' => '#6366F1',
                'sort_order' => 7,
                'aliases' => [
                    // All providers
                    'politics',
                    // NYTimes
                    'upshot',
                    // Common variations
                    'government', 'policy', 'election', 'political'
                ]
            ],
            [
                'name' => 'World',
                'description' => 'International news',
                'color' => '#06B6D4',
                'sort_order' => 8,
                'aliases' => [
                    // All providers
                    'world',
                    // Guardian
                    'uk-news', 'us-news', 'australia-news',
                    // NYTimes
                    'us',
                    // Common variations
                    'international', 'global', 'foreign'
                ]
            ],
            [
                'name' => 'Opinion',
                'description' => 'Opinion pieces and editorials',
                'color' => '#84CC16',
                'sort_order' => 9,
                'aliases' => [
                    // NYTimes
                    'opinion', 'sundayreview',
                    // Guardian
                    'commentisfree',
                    // Common variations
                    'editorial', 'commentary', 'analysis'
                ]
            ],
            [
                'name' => 'Lifestyle',
                'description' => 'Lifestyle and general interest',
                'color' => '#F97316',
                'sort_order' => 10,
                'aliases' => [
                    // Guardian
                    'lifeandstyle', 'food', 'travel',
                    // NYTimes
                    'style',
                    // Common variations
                    'lifestyle', 'fashion', 'home', 'living'
                ]
            ],
            [
                'name' => 'General',
                'description' => 'General news articles',
                'color' => '#6B7280',
                'sort_order' => 999,
                'aliases' => [
                    // NewsAPI
                    'general',
                    // NYTimes
                    'magazine', 'insider', 'obituaries',
                    // Common variations
                    'misc', 'other', 'news'
                ]
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($defaultCategories as $categoryData) {
            $slug = Str::slug($categoryData['name']);
            $category = NewsCategory::where('slug', $slug)->first();

            // Remove duplicates and normalize aliases
            $categoryData['aliases'] = array_unique(array_map('strtolower', $categoryData['aliases']));

            if ($category) {
                // Merge existing aliases with new ones
                $existingAliases = $category->aliases ?? [];
                $newAliases = array_unique(array_merge($existingAliases, $categoryData['aliases']));

                $category->update([
                    'description' => $categoryData['description'],
                    'aliases' => $newAliases,
                    'color' => $categoryData['color'],
                    'sort_order' => $categoryData['sort_order'],
                ]);
                $updated++;
            } else {
                NewsCategory::create($categoryData);
                $created++;
            }
        }

        $this->command->info("✅ Categories seeded: {$created} created, {$updated} updated");

        // Show what was seeded
        $this->command->table(
            ['Category', 'Aliases Count', 'Sample Aliases'],
            NewsCategory::orderBy('sort_order')->get()->map(function ($cat) {
                $aliases = $cat->aliases ?? [];
                $sample = count($aliases) > 5
                    ? implode(', ', array_slice($aliases, 0, 5)) . '...'
                    : implode(', ', $aliases);

                return [$cat->name, count($aliases), $sample];
            })->toArray()
        );
    }
}
