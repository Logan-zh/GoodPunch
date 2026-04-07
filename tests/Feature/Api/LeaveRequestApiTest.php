<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_own_leave_requests(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'hired_at' => now()->subYears(2)]);
        LeaveRequest::factory()->count(3)->create(['user_id' => $user->id, 'company_id' => $company->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/leave-requests');

        $response->assertOk()
            ->assertJsonStructure(['data', 'leave_entitlements'])
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_cancel_own_pending_leave_request(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $request = LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/leave-requests/{$request->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('leave_requests', ['id' => $request->id]);
    }

    public function test_user_cannot_cancel_another_users_leave_request(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $other = User::factory()->create(['company_id' => $company->id]);
        $request = LeaveRequest::factory()->create([
            'user_id' => $owner->id,
            'company_id' => $company->id,
            'status' => 'pending',
        ]);

        $this->actingAs($other, 'sanctum')
            ->deleteJson("/api/leave-requests/{$request->id}")
            ->assertStatus(403);
    }

    public function test_leave_request_requires_authentication(): void
    {
        $this->getJson('/api/leave-requests')->assertStatus(401);
    }
}
