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
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();                                                          // 主鍵
            $table->foreignId('company_id')->constrained()->onDelete('cascade');   // 關聯公司 ID
            $table->string('type');    // 假別類型：annual（特休）、personal（事假）、sick（病假）
            $table->decimal('min_years', 4, 1)->default(0); // 最低年資（年），用於特休分級設定
            $table->integer('days');                                               // 可請天數
            $table->timestamps();                                                  // 建立／更新時間
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
    }
};
