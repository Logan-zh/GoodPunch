<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SQLite uses TEXT for all string columns, so no DDL change is needed to
     * support 'overtime_in' and 'overtime_out' values in the 'type' column.
     * This migration adds 'correction_id' for punch correction tracking.
     */
    public function up(): void
    {
        Schema::table('punches', function (Blueprint $table) {
            $table->unsignedBigInteger('correction_id')->nullable()->after('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('punches', function (Blueprint $table) {
            $table->dropColumn('correction_id');
        });
    }
};
