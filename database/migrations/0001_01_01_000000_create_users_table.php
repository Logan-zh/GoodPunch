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
        Schema::create('users', function (Blueprint $table) {
            $table->id();                                          // 主鍵
            $table->string('name');                                // 姓名
            $table->string('email')->unique();                     // 電子郵件（唯一）
            $table->timestamp('email_verified_at')->nullable();    // 信箱驗證時間
            $table->string('password');                            // 密碼（雜湊）
            $table->rememberToken();                               // 記住我 Token
            $table->timestamps();                                  // 建立／更新時間
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();       // 信箱（主鍵）
            $table->string('token');                  // 重設密碼 Token
            $table->timestamp('created_at')->nullable(); // 建立時間
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();                  // Session ID（主鍵）
            $table->foreignId('user_id')->nullable()->index(); // 關聯使用者 ID
            $table->string('ip_address', 45)->nullable();     // 使用者 IP 位址
            $table->text('user_agent')->nullable();           // 瀏覽器 User Agent
            $table->longText('payload');                      // Session 資料
            $table->integer('last_activity')->index();        // 最後活動時間戳
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
