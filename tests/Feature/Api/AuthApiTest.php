<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_token(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'password' => bcrypt('correct')]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_creates_company_and_manager(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test Manager',
            'email' => 'manager@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'company_name' => 'Test Corp',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'role']]);

        $this->assertDatabaseHas('users', ['email' => 'manager@test.com', 'role' => 'manager']);
        $this->assertDatabaseHas('companies', ['name' => 'Test Corp']);
    }

    public function test_authenticated_user_can_get_own_profile(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email);
    }

    public function test_logout_revokes_token(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/auth/logout');

        $response->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }
}
