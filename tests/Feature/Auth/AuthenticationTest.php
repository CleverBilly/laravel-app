<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register_with_valid_data()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'code',
                     'message',
                     'data' => [
                         'user' => ['id', 'name', 'email'],
                         'token',
                         'token_type',
                         'expires_in',
                     ],
                     'timestamp',
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    /** @test */
    public function user_cannot_register_with_invalid_email()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function user_cannot_register_with_existing_email()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'user',
                         'token',
                         'token_type',
                         'expires_in',
                     ],
                 ]);
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('correct_password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => '邮箱或密码错误',
                 ]);
    }

    /** @test */
    public function authenticated_user_can_get_profile()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
                         ->getJson('/api/auth/me');

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'user' => [
                             'id' => $user->id,
                             'email' => $user->email,
                         ],
                     ],
                 ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_profile()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    /** @test */
    public function user_can_refresh_token()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
                         ->postJson('/api/auth/refresh');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'token',
                         'token_type',
                         'expires_in',
                     ],
                 ]);
    }

    /** @test */
    public function user_can_logout()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
                         ->postJson('/api/auth/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => '登出成功',
                 ]);
    }

    /** @test */
    public function token_becomes_invalid_after_logout()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // 登出
        $this->withHeader('Authorization', "Bearer {$token}")
             ->postJson('/api/auth/logout');

        // 尝试使用旧 token
        $response = $this->withHeader('Authorization', "Bearer {$token}")
                         ->getJson('/api/auth/me');

        $response->assertStatus(401);
    }
}

