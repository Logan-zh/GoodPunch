<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('meal_allowance', 10, 2)->default(0);
            $table->decimal('perfect_attendance_bonus', 10, 2)->default(0);
            $table->decimal('labor_insurance_employee', 10, 2)->default(0);
            $table->decimal('health_insurance_employee', 10, 2)->default(0);
            $table->decimal('overtime_hourly_rate', 10, 2)->default(0);
            $table->date('effective_from')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
    }
};
