<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('punches', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->after('punch_time')->nullable();  // 緯度（可空，精度到小數第8位）
            $table->decimal('longitude', 11, 8)->after('latitude')->nullable();  // 經度（可空，精度到小數第8位）
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('punches', function (Blueprint $table) {
            //
        });
    }
};
