<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('punch_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('correction_date');
            $table->string('correction_type', 20); // in|out|overtime_in|overtime_out
            $table->dateTime('requested_time');
            $table->text('reason')->nullable();
            $table->text('approval_note')->nullable();
            $table->string('status', 20)->default('pending');// pending|approved|rejected
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('original_punch_id')->nullable();
            $table->timestamps();

            $table->foreign('approver_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('punch_corrections');
    }
};
