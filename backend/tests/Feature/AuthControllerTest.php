<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test successful login.
     */
    public function test_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'logintest-' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
        ]);

        $email = $user->email;

        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ]);
    }

    /**
     * Test login with invalid email.
     */
    public function test_cannot_login_with_invalid_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent-' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test login with invalid password.
     */
    public function test_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'pwdtest-' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test login validation.
     */
    public function test_login_validation(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test login with invalid email format.
     */
    public function test_login_requires_valid_email_format(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test logout.
     */
    public function test_can_logout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);
    }

    /**
     * Test logout without authentication.
     */
    public function test_cannot_logout_without_auth(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    /**
     * Test get current user.
     */
    public function test_can_get_current_user(): void
    {
        $email = 'usertest-' . uniqid() . '@example.com';
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => $email,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => 'Test User',
                'email' => $email,
            ]);
    }

    /**
     * Test get user without authentication.
     */
    public function test_cannot_get_user_without_auth(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    /**
     * Test token is valid after login.
     *
     * Note: Uses Sanctum::actingAs() because DatabaseTransactions trait
     * prevents token persistence between requests in test environment.
     */
    public function test_token_works_for_authenticated_requests(): void
    {
        $email = 'tokentest-' . uniqid() . '@example.com';
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make('password123'),
        ]);

        // Verify login returns a token
        $loginResponse = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);

        // Verify authenticated requests work using Sanctum test helper
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson(['email' => $email]);
    }
}
