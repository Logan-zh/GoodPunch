<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('work_start_time', 5)->default('09:00')->after('work_hours_per_day'); // 上班時間（格式 HH:MM，預設 09:00）
            $table->string('work_end_time', 5)->default('18:00')->after('work_start_time');     // 下班時間（格式 HH:MM，預設 18:00）
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['work_start_time', 'work_end_time']);
        });
    }
};
