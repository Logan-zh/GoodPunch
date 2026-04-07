<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyManagementApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->admin()->create(['company_id' => null]);
    }

    private function managerUser(): User
    {
        return User::factory()->create([
            'role' => 'manager',
            'company_id' => Company::factory()->create()->id,
        ]);
    }

    public function test_admin_can_list_companies(): void
    {
        Company::factory()->count(3)->create();

        $this->actingAs($this->adminUser(), 'sanctum')
            ->getJson('/api/admin/companies')
            ->assertOk()
            ->assertJsonCount(3);
    }

    public function test_non_admin_cannot_list_companies(): void
    {
        $this->actingAs($this->managerUser(), 'sanctum')
            ->getJson('/api/admin/companies')
            ->assertForbidden();
    }

    public function test_admin_can_create_a_company(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum')
            ->postJson('/api/admin/companies', [
                'name' => 'New Corp',
                'code' => 'NEWCORP',
                'tax_id' => '87654321',
            ])
            ->assertStatus(201)
            ->assertJsonPath('name', 'New Corp');

        $this->assertDatabaseHas('companies', ['code' => 'NEWCORP', 'name' => 'New Corp']);
    }

    public function test_create_company_requires_name(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum')
            ->postJson('/api/admin/companies', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_company_name_must_be_unique(): void
    {
        Company::factory()->create(['name' => 'Taken Corp']);

        $this->actingAs($this->adminUser(), 'sanctum')
            ->postJson('/api/admin/companies', ['name' => 'Taken Corp'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_admin_can_delete_a_company(): void
    {
        $company = Company::factory()->create();

        $this->actingAs($this->adminUser(), 'sanctum')
            ->deleteJson("/api/admin/companies/{$company->id}")
            ->assertOk();

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    public function test_unauthenticated_cannot_access_companies(): void
    {
        $this->getJson('/api/admin/companies')->assertStatus(401);
    }
}
