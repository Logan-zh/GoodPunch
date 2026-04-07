<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentApiTest extends TestCase
{
    use RefreshDatabase;

    private function setupCompanyWithManager(): array
    {
        $company = Company::factory()->create();
        $manager = User::factory()->create(['role' => 'manager', 'company_id' => $company->id]);

        return [$company, $manager];
    }

    public function test_manager_can_list_departments(): void
    {
        [$company, $manager] = $this->setupCompanyWithManager();
        Department::factory()->count(2)->create(['company_id' => $company->id]);

        $this->actingAs($manager, 'sanctum')
            ->getJson('/api/admin/departments')
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_manager_can_assign_user_to_department(): void
    {
        [$company, $manager] = $this->setupCompanyWithManager();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $staff = User::factory()->create(['company_id' => $company->id, 'department_id' => null]);

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/admin/departments/{$department->id}/assign", ['user_id' => $staff->id])
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $staff->id, 'department_id' => $department->id]);
    }

    public function test_assign_requires_user_id(): void
    {
        [$company, $manager] = $this->setupCompanyWithManager();
        $department = Department::factory()->create(['company_id' => $company->id]);

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/admin/departments/{$department->id}/assign", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_cannot_assign_user_from_different_company(): void
    {
        [$company, $manager] = $this->setupCompanyWithManager();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id, 'department_id' => null]);

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/admin/departments/{$department->id}/assign", ['user_id' => $otherUser->id])
            ->assertOk();

        // Silently ignored because company_id does not match
        $this->assertDatabaseHas('users', ['id' => $otherUser->id, 'department_id' => null]);
    }

    public function test_cannot_delete_department_with_assigned_users(): void
    {
        [$company, $manager] = $this->setupCompanyWithManager();
        $department = Department::factory()->create(['company_id' => $company->id]);
        User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id]);

        $this->actingAs($manager, 'sanctum')
            ->deleteJson("/api/admin/departments/{$department->id}")
            ->assertStatus(422);
    }

    public function test_regular_user_cannot_access_departments(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'user']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/departments')
            ->assertForbidden();
    }
}
