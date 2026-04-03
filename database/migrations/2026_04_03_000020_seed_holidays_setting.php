<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Seed holidays setting for every company
        Company::all()->each(function (Company $company) {
            $exists = DB::table('settings')
                ->where('company_id', $company->id)
                ->where('key', 'holidays')
                ->exists();

            if (!$exists) {
                DB::table('settings')->insert([
                    'company_id' => $company->id,
                    'key'        => 'holidays',
                    'value'      => '[]',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'holidays')->delete();
    }
};
