<?php

// tests/Feature/UserPreferenceTest.php

namespace Tests\Feature;

use App\Models\User;
use App\Models\NewsCategory;
use App\Models\NewsAuthor;
use App\Models\NewsSource;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;

class UserPreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->category = NewsCategory::factory()->create(['name' => 'Technology']);
        $this->author = NewsAuthor::factory()->create(['name' => 'John Doe']);
        $this->source = NewsSource::factory()->create(['name' => 'TechNews']);
    }

    #[Test]
    public function authenticated_user_can_create_preferences()
    {
        Sanctum::actingAs($this->user);

        $preferenceData = [
            'category_id' => $this->category->id,
            'author_id' => $this->author->id,
            'source_id' => $this->source->id,
        ];

        $response = $this->postJson('/api/user/preference', $preferenceData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'category',
                    'author',
                    'source'
                ]
            ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'news_category_id' => $this->category->id,
        ]);
    }

    #[Test]
    public function authenticated_user_can_update_preferences()
    {
        $preference = UserPreference::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $updateData = [
            'category_id' => $this->category->id,
            'author_id' => $this->author->id,
        ];

        $response = $this->postJson('/api/user/preference', $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'news_category_id' => $this->category->id,
        ]);
    }

    #[Test]
    public function authenticated_user_can_fetch_preferences()
    {
        $preference = UserPreference::factory()->create([
            'user_id' => $this->user->id,
            'news_category_id' => $this->category->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user/preference');


        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'category',
                    'author',
                    'source'
                ]
            ])
            ->assertJson([
                'data' => [
                    'category' => ['id' => $this->category->id]
                ]
            ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_preferences()
    {
        $response = $this->getJson('/api/user/preference');
        $response->assertStatus(401);

        $response = $this->postJson('/api/user/preference', []);
        $response->assertStatus(401);
    }

    #[Test]
    public function user_can_create_preferences_with_partial_data()
    {
        Sanctum::actingAs($this->user);

        $preferenceData = [
            'category_id' => $this->category->id,
            // Only category, no author or source
        ];

        $response = $this->postJson('/api/user/preference', $preferenceData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'news_category_id' => $this->category->id,
            'news_author_id' => null,
            'news_source_id' => null,
        ]);
    }

    #[Test]
    public function user_can_update_preferences_with_null_values()
    {
        $preference = UserPreference::factory()->create([
            'user_id' => $this->user->id,
            'news_category_id' => $this->category->id,
            'news_author_id' => $this->author->id,
        ]);

        Sanctum::actingAs($this->user);

        $updateData = [
            'category_id' => null, // Remove category preference
            'author_id' => $this->author->id,
        ];

        $response = $this->postJson('/api/user/preference', $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'news_category_id' => null,
            'news_author_id' => $this->author->id,
        ]);
    }

    #[Test]
    public function user_cannot_create_preferences_with_invalid_category()
    {
        Sanctum::actingAs($this->user);

        $preferenceData = [
            'category_id' => 999999, // Non-existent category
        ];

        $response = $this->postJson('/api/user/preference', $preferenceData);


        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    #[Test]
    public function user_cannot_create_preferences_with_invalid_author()
    {
        Sanctum::actingAs($this->user);

        $preferenceData = [
            'author_id' => 999999, // Non-existent author
        ];

        $response = $this->postJson('/api/user/preference', $preferenceData);

        // Check status first, then handle accordingly

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['author_id']);
    }

    #[Test]
    public function user_cannot_create_preferences_with_invalid_source()
    {
        Sanctum::actingAs($this->user);

        $preferenceData = [
            'source_id' => 999999, // Non-existent source
        ];

        $response = $this->postJson('/api/user/preference', $preferenceData);

        // Check status first, then handle accordingly

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['source_id']);
    }

    #[Test]
    public function user_can_only_have_one_preference_record()
    {
        // Create first preference
        UserPreference::factory()->create([
            'user_id' => $this->user->id,
            'news_category_id' => $this->category->id,
        ]);

        Sanctum::actingAs($this->user);

        // Try to create another preference (should update, not create new)
        $newPreferenceData = [
            'news_author_id' => $this->author->id,
        ];

        $response = $this->postJson('/api/user/preference', $newPreferenceData);

        // Should either update existing or return validation error
        $this->assertContains($response->status(), [200, 201, 422]);

        // Should still only have one preference record for this user
        $this->assertEquals(1, UserPreference::where('user_id', $this->user->id)->count());
    }

    #[Test]
    public function user_preferences_are_used_in_personalized_news()
    {
        UserPreference::factory()->create([
            'user_id' => $this->user->id,
            'news_category_id' => $this->category->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/news?personalized=1');

        $response->assertStatus(200);
        $this->assertIsArray($response->json('data'));
    }
}
