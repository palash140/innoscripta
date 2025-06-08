<?php

// tests/Feature/NewsTest.php

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

class NewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data with minimal fields
        $this->category = NewsCategory::factory()->create(['name' => 'Technology']);
        $this->author = NewsAuthor::factory()->create(['name' => 'John Doe']);
        $this->source = NewsSource::factory()->create(['name' => 'TechNews']);

        // Create news articles
        News::factory()->count(5)->create([
            'news_category_id' => $this->category->id,
            'news_author_id' => $this->author->id,
            'news_source_id' => $this->source->id,
        ]);
    }

    #[Test]
    public function can_fetch_news_without_authentication()
    {
        $response = $this->getJson('/api/news');

        $response->assertStatus(401); // Unauthorized
    }

    #[Test]
    public function authenticated_user_can_fetch_news()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/news');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                    ]
                ]
            ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_fetch_news()
    {
        $response = $this->getJson('/api/news');

        $response->assertStatus(401);
    }

    #[Test]
    public function can_filter_news_by_category()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/news?category_id={$this->category->id}");

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    #[Test]
    public function can_filter_news_by_author()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/news?author_id={$this->author->id}");

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    #[Test]
    public function can_filter_news_by_source()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/news?source_id={$this->source->id}");

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    #[Test]
    public function can_filter_news_by_provider()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create news with specific provider
        News::factory()->create([
            'provider' => 'test-provider',
            'news_category_id' => $this->category->id,
            'news_author_id' => $this->author->id,
            'news_source_id' => $this->source->id,
        ]);

        $response = $this->getJson("/api/news?provider=test-provider");

        $response->assertStatus(200);
    }

    #[Test]
    public function can_filter_news_by_date_range()
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
    public function authenticated_user_can_fetch_personalized_news()
    {
        $user = User::factory()->create();

        // Create user preference
        UserPreference::factory()->create([
            'user_id' => $user->id,
            'news_category_id' => $this->category->id,
            'news_author_id' => $this->author->id,
            'news_source_id' => $this->source->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/news?personalized=1');

        $response->assertStatus(200);
        $this->assertIsArray($response->json('data'));
    }

    #[Test]
    public function personalized_news_requires_authentication()
    {
        $response = $this->getJson('/api/news?personalized=1');

        $response->assertStatus(401);
    }

    #[Test]
    public function can_fetch_news_categories()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/news/categories');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json());
    }

    #[Test]
    public function can_fetch_news_authors()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/news/authors');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json());
    }

    #[Test]
    public function can_fetch_news_sources()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/news/sources');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json());
    }

    #[Test]
    public function returns_empty_array_when_no_news_match_filters()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/news?category_id=999999');

        $response->assertStatus(200);
        $this->assertIsArray($response->json('data'));
    }

    #[Test]
    public function can_paginate_news_results()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create more news for pagination test
        News::factory()->count(20)->create([
            'news_category_id' => $this->category->id,
            'news_author_id' => $this->author->id,
            'news_source_id' => $this->source->id,
        ]);

        $response = $this->getJson('/api/news?per_page=10');

        $response->assertStatus(200);
        $data = $response->json();

        // Check if pagination structure exists
        $this->assertArrayHasKey('data', $data);
        $this->assertLessThanOrEqual(10, count($data['data']));
    }
}
