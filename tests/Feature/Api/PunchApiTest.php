<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PunchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_punch_in(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/punches', ['type' => 'in']);

        $response->assertStatus(201)
            ->assertJsonPath('type', 'in')
            ->assertJsonPath('user_id', $user->id);

        $this->assertDatabaseHas('punches', ['user_id' => $user->id, 'type' => 'in']);
    }

    public function test_duplicate_consecutive_punch_type_is_rejected(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user, 'sanctum')->postJson('/api/punches', ['type' => 'in']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/punches', ['type' => 'in']);

        $response->assertStatus(422);
    }

    public function test_punch_requires_authentication(): void
    {
        $this->postJson('/api/punches', ['type' => 'in'])->assertStatus(401);
    }

    public function test_invalid_punch_type_is_rejected(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/punches', ['type' => 'invalid_type']);

        $response->assertStatus(422);
    }
}
