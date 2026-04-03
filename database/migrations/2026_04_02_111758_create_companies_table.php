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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();                                    // 主鍵
            $table->string('name');                          // 公司名稱
            $table->string('code')->unique();                // 公司代碼（唯一）
            $table->string('tax_id')->unique()->nullable();  // 統一編號（唯一，可空）
            $table->string('principal')->nullable();         // 負責人姓名
            $table->string('phone')->nullable();             // 聯絡電話
            $table->text('address')->nullable();             // 公司地址
            $table->boolean('status')->default(true);        // 狀態：true（啟用）、false（停用）
            $table->timestamps();                            // 建立／更新時間
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
