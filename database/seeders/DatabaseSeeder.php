<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a Default Company for initial development
        $company = \App\Models\Company::create([
            'name' => 'Default Company',
            'code' => 'DEFAULT',
            'tax_id' => '00000000',
            'principal' => 'System Admin',
            'phone' => '0000000000',
            'address' => 'System HQ',
        ]);

        // Super Admin (No company_id, role admin can see everything)
        User::factory()->admin()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('admin123'),
            'company_id' => null, 
        ]);

        // Enterprise Manager (Company A)
        User::factory()->create([
            'name' => 'Default Manager',
            'email' => 'manager@example.com',
            'password' => bcrypt('manager123'),
            'role' => 'manager',
            'company_id' => $company->id,
        ]);

        // Regular Staff (Company A)
        User::factory()->create([
            'name' => 'Default Staff',
            'email' => 'staff@example.com',
            'password' => bcrypt('staff123'),
            'role' => 'user',
            'company_id' => $company->id,
        ]);
    }
}
