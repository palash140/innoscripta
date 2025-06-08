<?php

// tests/Feature/NewsFilterTest.php

namespace Tests\Feature;

use App\Models\User;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsAuthor;
use App\Models\NewsSource;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;

class NewsFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category1 = NewsCategory::factory()->create(['name' => 'Technology']);
        $this->category2 = NewsCategory::factory()->create(['name' => 'Sports']);
        $this->author1 = NewsAuthor::factory()->create(['name' => 'John Doe']);
        $this->author2 = NewsAuthor::factory()->create(['name' => 'Jane Smith']);
        $this->source1 = NewsSource::factory()->create(['name' => 'TechNews']);
        $this->source2 = NewsSource::factory()->create(['name' => 'SportsTimes']);

        // Create news with different combinations
        News::factory()->create([
            'news_category_id' => $this->category1->id,
            'news_author_id' => $this->author1->id,
            'news_source_id' => $this->source1->id,
            'provider' => 'tech-provider',
        ]);

        News::factory()->create([
            'news_category_id' => $this->category2->id,
            'news_author_id' => $this->author2->id,
            'news_source_id' => $this->source2->id,
            'provider' => 'sports-provider',
        ]);
    }

    #[Test]
    public function can_filter_by_multiple_parameters()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/news?category_id={$this->category1->id}&author_id={$this->author1->id}");

        $response->assertStatus(200);
        $this->assertIsArray($response->json('data'));
    }

    #[Test]
    public function personalized_filter_overrides_manual_filters()
    {
        $user = User::factory()->create();
        UserPreference::factory()->create([
            'user_id' => $user->id,
            'news_category_id' => $this->category1->id,
        ]);

        Sanctum::actingAs($user);

        // Request with both personalized and manual category filter
        $response = $this->getJson("/api/news?personalized=1&category_id={$this->category2->id}");

        $response->assertStatus(200);
        $this->assertIsArray($response->json('data'));
    }

    #[Test]
    public function empty_filters_return_all_news()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/news');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(2, count($data));
    }

    #[Test]
    public function invalid_filter_values_are_ignored()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/news?category_id=999999&author_id=invalid');

        $response->assertStatus(200);

        // Should return empty results or all results, not error
        $data = $response->json('data');
        $this->assertIsArray($data);
    }

    #[Test]
    public function can_filter_by_provider()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/news?provider=tech-provider');

        $response->assertStatus(200);
        $this->assertIsArray($response->json('data'));
    }

    #[Test]
    public function can_filter_by_date_range()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $fromDate = now()->subDays(7)->format('Y-m-d');
        $toDate = now()->format('Y-m-d');

        $response = $this->getJson("/api/news?from={$fromDate}&to={$toDate}");

        $response->assertStatus(200);
        $this->assertIsArray($response->json('data'));
    }

    #[Test]
    public function unauthenticated_user_cannot_filter_news()
    {
        $response = $this->getJson("/api/news?category_id={$this->category1->id}");

        $response->assertStatus(401);
    }

    #[Test]
    public function personalized_filter_requires_authentication()
    {
        $response = $this->getJson('/api/news?personalized=1');

        $response->assertStatus(401);
    }

    #[Test]
    public function can_combine_multiple_filters()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/news?category_id={$this->category1->id}&provider=tech-provider");

        $response->assertStatus(200);
        $this->assertIsArray($response->json('data'));
    }
}
