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
        Schema::create('punches', function (Blueprint $table) {
            $table->id();                                                         // 主鍵
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();       // 關聯員工 ID（刪除員工時一併刪除）
            $table->string('type');    // 打卡類型：in（上班）、out（下班）
            $table->timestamp('punch_time');                                      // 打卡時間
            $table->timestamps();                                                 // 建立／更新時間
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('punches');
    }
};
