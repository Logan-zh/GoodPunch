<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->admin()->create(['company_id' => null]);
    }

    private function managerUser(): User
    {
        $company = Company::factory()->create();

        return User::factory()->create(['role' => 'manager', 'company_id' => $company->id]);
    }

    public function test_admin_can_view_companies_index(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('admin.companies.index'))
            ->assertOk();
    }

    public function test_non_admin_cannot_view_companies_index(): void
    {
        $this->actingAs($this->managerUser())
            ->get(route('admin.companies.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_company(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('admin.companies.store'), [
                'name' => 'New Corp',
                'code' => 'NEWCORP',
                'tax_id' => '87654321',
                'principal' => 'Jane Doe',
                'phone' => '0987654321',
                'address' => '456 Business Ave',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('companies', ['code' => 'NEWCORP', 'name' => 'New Corp']);
    }

    public function test_create_company_requires_name_and_code(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('admin.companies.store'), [])
            ->assertSessionHasErrors(['name', 'code']);
    }

    public function test_create_company_code_must_be_unique(): void
    {
        Company::factory()->create(['code' => 'TAKEN']);

        $this->actingAs($this->adminUser())
            ->post(route('admin.companies.store'), [
                'name' => 'Another Corp',
                'code' => 'TAKEN',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_admin_can_delete_a_company(): void
    {
        $company = Company::factory()->create();

        $this->actingAs($this->adminUser())
            ->delete(route('admin.companies.destroy', $company))
            ->assertRedirect();

        $this->assertModelMissing($company);
    }

    public function test_non_admin_cannot_create_a_company(): void
    {
        $this->actingAs($this->managerUser())
            ->post(route('admin.companies.store'), [
                'name' => 'Sneaky Corp',
                'code' => 'SNEAK',
            ])
            ->assertForbidden();
    }
}
