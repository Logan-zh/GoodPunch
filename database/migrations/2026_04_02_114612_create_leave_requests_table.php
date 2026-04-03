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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();                                                          // 主鍵
            $table->foreignId('user_id')->constrained()->onDelete('cascade');      // 申請員工 ID
            $table->foreignId('company_id')->constrained()->onDelete('cascade');   // 所屬公司 ID
            $table->string('type');    // 假別：annual（特休）、personal（事假）、sick（病假）
            $table->dateTime('start_at');                                          // 請假開始時間
            $table->dateTime('end_at');                                            // 請假結束時間
            $table->decimal('hours', 8, 2);                                        // 請假總時數
            $table->text('reason')->nullable();                                    // 請假原因
            $table->string('status')->default('pending'); // 狀態：pending（待審）、approved（核准）、rejected（拒絕）、cancelled（取消）
            $table->integer('current_step')->default(0);                           // 目前審核步驟索引
            $table->timestamps();                                                  // 建立／更新時間
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
