<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function api_endpoints_are_rate_limited()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Make multiple requests to test rate limiting
        for ($i = 0; $i < 65; $i++) {
            $response = $this->getJson('/api/news');

            if ($i < 60) {
                $response->assertStatus(200);
            } else {
                $response->assertStatus(429); // Too Many Requests
                break;
            }
        }
    }
}
