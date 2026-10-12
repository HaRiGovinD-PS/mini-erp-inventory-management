<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    
    public function test_admin_can_log_in(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email' => 'admin-test@example.com',
            'password' => 'admin@123',
        ]);

        $response = $this->post(route('login.submit'), [
            'email' => 'admin-test@example.com',
            'password' => 'admin@123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_non_admin_user_cannot_log_in_to_admin_panel(): void
    {
        User::factory()->create([
            'is_admin' => false,
            'email' => 'user-test@example.com',
            'password' => 'User@12345',
        ]);

        $response = $this->post(route('login.submit'), [
            'email' => 'user-test@example.com',
            'password' => 'User@12345',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

}
