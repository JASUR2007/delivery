<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase {
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void {
        $user = User::factory()->create(['role' => UserRole::Executor]);
        $this->actingAs($user);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
