<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('work_hours_per_day', 4, 1)->default(8.0)->after('address');             // 每日工作時數（預設 8 小時）
            $table->json('leave_approval_chain')->nullable()->after('work_hours_per_day');           // 請假審核鏈設定（JSON 格式）
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->onDelete('set null')->after('company_id'); // 直屬主管 ID（可空，主管被刪除時設為 null）
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['work_hours_per_day', 'leave_approval_chain']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_id');
        });
    }
};
