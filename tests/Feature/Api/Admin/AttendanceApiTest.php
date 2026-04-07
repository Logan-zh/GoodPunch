<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_view_attendance(): void
    {
        $company = Company::factory()->create();
        $manager = User::factory()->create(['company_id' => $company->id, 'role' => 'manager']);

        $response = $this->actingAs($manager, 'sanctum')
            ->getJson('/api/admin/attendance');

        $response->assertOk()
            ->assertJsonStructure(['data', 'stats' => ['totalStaff', 'punchedInToday', 'totalPunchesToday']]);
    }

    public function test_regular_user_cannot_view_attendance(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'user']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/attendance')
            ->assertStatus(403);
    }

    public function test_attendance_can_be_filtered_by_date(): void
    {
        $company = Company::factory()->create();
        $manager = User::factory()->create(['company_id' => $company->id, 'role' => 'manager']);

        $response = $this->actingAs($manager, 'sanctum')
            ->getJson('/api/admin/attendance?date='.now()->toDateString());

        $response->assertOk();
    }

    public function test_unauthenticated_cannot_view_attendance(): void
    {
        $this->getJson('/api/admin/attendance')->assertStatus(401);
    }
}
