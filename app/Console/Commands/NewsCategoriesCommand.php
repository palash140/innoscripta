<?php

namespace App\Console\Commands;

use App\Models\NewsCategory;
use App\Services\CategoryService;
use Illuminate\Console\Command;

class NewsCategoriesCommand extends Command
{
    protected $signature = 'news:categories
                          {action : Action to perform (list, create, alias, stats, find)}
                          {--name= : Category name}
                          {--alias= : Alias to add}
                          {--color= : Category color}
                          {--search= : Search term for find action}';

    protected $description = 'Manage news categories';

    public function handle(CategoryService $categoryService): int
    {
        $action = $this->argument('action');

        return match($action) {
            'list' => $this->listCategories(),
            'create' => $this->createCategory(),
            'alias' => $this->addAlias($categoryService),
            'stats' => $this->showStats($categoryService),
            'find' => $this->findCategory($categoryService),
            default => $this->invalidAction()
        };
    }

    private function listCategories(): int
    {
        $categories = NewsCategory::orderBy('sort_order')->orderBy('name')->get();

        if ($categories->isEmpty()) {
            $this->warn('No categories found.');
            $this->info('Run "php artisan db:seed --class=NewsCategorySeeder" to create default categories.');
            return self::SUCCESS;
        }

        $this->info("📂 Found {$categories->count()} categories:");

        $this->table(
            ['ID', 'Name', 'Slug', 'Color', 'News Count', 'Aliases Count', 'Sample Aliases'],
            $categories->map(function ($category) {
                $aliases = $category->aliases ?? [];
                $sampleAliases = count($aliases) > 3
                    ? implode(', ', array_slice($aliases, 0, 3)) . '...'
                    : implode(', ', $aliases);

                return [
                    $category->id,
                    $category->name,
                    $category->slug,
                    $category->color,
                    $category->news()->count(),
                    count($aliases),
                    $sampleAliases,
                ];
            })->toArray()
        );

        return self::SUCCESS;
    }

    private function createCategory(): int
    {
        $name = $this->option('name') ?: $this->ask('Category name');
        $color = $this->option('color') ?: $this->ask('Color (hex)', '#6B7280');

        if (!$name) {
            $this->error('Category name is required');
            return self::FAILURE;
        }

        // Check if category already exists
        $existing = NewsCategory::where('name', $name)
            ->orWhere('slug', \Illuminate\Support\Str::slug($name))
            ->first();

        if ($existing) {
            $this->error("Category '{$name}' already exists (ID: {$existing->id})");
            return self::FAILURE;
        }

        $category = NewsCategory::create([
            'name' => $name,
            'color' => $color,
            'sort_order' => NewsCategory::max('sort_order') + 1,
            'aliases' => [strtolower($name)]
        ]);

        $this->info("✅ Created category: {$category->name} (ID: {$category->id})");
        return self::SUCCESS;
    }

    private function addAlias(CategoryService $categoryService): int
    {
        $name = $this->option('name') ?: $this->ask('Category name');
        $alias = $this->option('alias') ?: $this->ask('Alias to add');

        if (!$name || !$alias) {
            $this->error('Both category name and alias are required');
            return self::FAILURE;
        }

        if ($categoryService->addAliasToCategory($name, $alias)) {
            $this->info("✅ Added alias '{$alias}' to category '{$name}'");
            return self::SUCCESS;
        } else {
            $this->error("❌ Category '{$name}' not found");
            return self::FAILURE;
        }
    }

    private function showStats(CategoryService $categoryService): int
    {
        $stats = $categoryService->getCategoryStats();

        if (empty($stats)) {
            $this->warn('No categories found.');
            return self::SUCCESS;
        }

        $this->info('📊 Category Statistics:');

        $this->table(
            ['Category', 'News Count', 'Percentage', 'Aliases Count', 'Top Aliases'],
            collect($stats)->map(function ($stat) {
                $topAliases = count($stat['aliases']) > 5
                    ? implode(', ', array_slice($stat['aliases'], 0, 5)) . '...'
                    : implode(', ', $stat['aliases']);

                return [
                    $stat['name'],
                    number_format($stat['news_count']),
                    $stat['percentage'] . '%',
                    count($stat['aliases']),
                    $topAliases,
                ];
            })->toArray()
        );

        $totalNews = collect($stats)->sum('news_count');
        $totalCategories = count($stats);

        $this->line('');
        $this->info("Total Categories: {$totalCategories}");
        $this->info("Total News Articles: " . number_format($totalNews));

        return self::SUCCESS;
    }

    private function findCategory(CategoryService $categoryService): int
    {
        $search = $this->option('search') ?: $this->ask('Search for category');

        if (!$search) {
            $this->error('Search term is required');
            return self::FAILURE;
        }

        $category = $categoryService->findCategory($search);

        if ($category) {
            $this->info("✅ Found category: {$category->name}");
            $this->table(
                ['Property', 'Value'],
                [
                    ['ID', $category->id],
                    ['Name', $category->name],
                    ['Slug', $category->slug],
                    ['Description', $category->description ?: 'N/A'],
                    ['Color', $category->color],
                    ['Sort Order', $category->sort_order],
                    ['News Count', $category->news()->count()],
                    ['Aliases', implode(', ', $category->aliases ?? [])],
                    ['Created', $category->created_at->format('Y-m-d H:i:s')],
                    ['Updated', $category->updated_at->format('Y-m-d H:i:s')],
                ]
            );
        } else {
            $this->warn("❌ No category found matching: '{$search}'");

            // Suggest similar categories
            $similar = NewsCategory::where('name', 'LIKE', "%{$search}%")
                ->orWhereJsonContains('aliases', strtolower($search))
                ->limit(5)
                ->get();

            if ($similar->isNotEmpty()) {
                $this->info('💡 Similar categories found:');
                foreach ($similar as $cat) {
                    $this->line("  • {$cat->name} (ID: {$cat->id})");
                }
            }
        }

        return self::SUCCESS;
    }

    private function invalidAction(): int
    {
        $this->error('Invalid action.');
        $this->info('Available actions: list, create, alias, stats, find');
        $this->line('');
        $this->info('Examples:');
        $this->line('  php artisan news:categories list');
        $this->line('  php artisan news:categories create --name="Cryptocurrency"');
        $this->line('  php artisan news:categories alias --name="Technology" --alias="blockchain"');
        $this->line('  php artisan news:categories stats');
        $this->line('  php artisan news:categories find --search="tech"');

        return self::FAILURE;
    }
}
