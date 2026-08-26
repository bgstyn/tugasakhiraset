<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that unauthenticated users are redirected to login.
     */
    public function test_the_application_redirects_unauthenticated_users(): void
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that login page is accessible.
     */
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
    }

    /**
     * Test that authenticated users can access the dashboard.
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'role' => 'teknisi',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }
}
