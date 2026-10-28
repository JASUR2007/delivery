<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAuthenticationTest extends TestCase {
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_page_from_home(): void {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_login_page_renders_successfully(): void {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_user_can_login_with_valid_credentials(): void {
        $user = User::factory()->create([
            'email' => 'test@davlat.uz',
            'password' => bcrypt('password123'),
            'role' => UserRole::Executor,
        ]);

        $response = $this->post('/login', [
            'email' => 'test@davlat.uz',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_password(): void {
        User::factory()->create([
            'email' => 'test@davlat.uz',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'test@davlat.uz',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_quick_login_via_demo_button(): void {
        $user = User::factory()->create([
            'role' => UserRole::Supervisor,
        ]);

        $response = $this->post('/quick-login', [
            'user_id' => $user->id,
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_can_logout(): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
