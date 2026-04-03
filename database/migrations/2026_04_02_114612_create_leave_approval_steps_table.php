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
        Schema::create('leave_approval_steps', function (Blueprint $table) {
            $table->id();                                                                           // 主鍵
            $table->foreignId('leave_request_id')->constrained()->onDelete('cascade');              // 關聯請假申請 ID
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null'); // 審核人 ID（審核人被刪除時設為 null）
            $table->integer('step_index');                                                          // 審核步驟順序（從 0 開始）
            $table->string('step_name')->nullable();                                                // 步驟名稱（如「主管審核」）
            $table->string('status')->default('pending'); // 審核狀態：pending（待審）、approved（核准）、rejected（拒絕）
            $table->text('comment')->nullable();                                                    // 審核意見
            $table->timestamp('action_at')->nullable();                                             // 審核動作時間
            $table->timestamps();                                                                   // 建立／更新時間
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_approval_steps');
    }
};
