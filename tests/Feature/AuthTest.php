<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SeedsBasicData;

class AuthTest extends TestCase
{
    use RefreshDatabase, SeedsBasicData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBasics();
    }

    public function test_a_user_can_register_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);

        $user = User::query()->where('email', 'jane@example.com')->first();
        $this->assertTrue($user->hasRole('customer'));
    }

    public function test_a_user_can_login_with_correct_credentials(): void
    {
        User::factory()->create(['email' => 'login@example.com', 'password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_incorrect_password(): void
    {
        User::factory()->create(['email' => 'login2@example.com', 'password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login2@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
    }

    public function test_authenticated_user_can_fetch_their_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/auth/me');

        $response->assertOk()->assertJsonPath('user.id', $user->id);
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }
}
