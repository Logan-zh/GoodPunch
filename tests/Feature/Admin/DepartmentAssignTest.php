<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentAssignTest extends TestCase
{
    use RefreshDatabase;

    private function setupCompanyWithManager(): array
    {
        $company = Company::factory()->create();
        $manager = User::factory()->create(['role' => 'manager', 'company_id' => $company->id]);

        return [$company, $manager];
    }

    public function test_manager_can_assign_user_to_department(): void
    {
        [$company, $manager] = $this->setupCompanyWithManager();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $staff = User::factory()->create(['company_id' => $company->id, 'department_id' => null]);

        $this->actingAs($manager)
            ->post(route('admin.departments.assign-user', $department), ['user_id' => $staff->id])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $staff->id, 'department_id' => $department->id]);
    }

    public function test_assign_requires_user_id(): void
    {
        [$company, $manager] = $this->setupCompanyWithManager();
        $department = Department::factory()->create(['company_id' => $company->id]);

        $this->actingAs($manager)
            ->post(route('admin.departments.assign-user', $department), [])
            ->assertSessionHasErrors('user_id');
    }

    public function test_cannot_assign_user_from_different_company(): void
    {
        [$company, $manager] = $this->setupCompanyWithManager();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($manager)
            ->post(route('admin.departments.assign-user', $department), ['user_id' => $otherUser->id])
            ->assertRedirect();

        // The update is silently ignored due to company_id check
        $this->assertDatabaseHas('users', ['id' => $otherUser->id, 'department_id' => null]);
    }

    public function test_admin_can_view_users_scoped_to_company(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create(['company_id' => null]);
        User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['company_id' => $company->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Users/Index')
                ->where('targetCompanyId', $company->id)
            );
    }
}
