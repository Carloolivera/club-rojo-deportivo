<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        User::factory()->create(['email' => 'test@club.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'test@club.com',
                'password' => 'wrong-password',
            ]);
        }

        $this->post('/login', [
            'email' => 'test@club.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_successful_login_redirects(): void
    {
        User::factory()->create([
            'email' => 'admin@club.com',
            'password' => bcrypt('correct-password'),
        ]);

        $this->post('/login', [
            'email' => 'admin@club.com',
            'password' => 'correct-password',
        ])->assertRedirect();
    }
}
