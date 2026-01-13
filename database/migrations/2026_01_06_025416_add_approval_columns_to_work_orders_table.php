<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('work_order_general_affairs', function (Blueprint $table) {
            // Kolom untuk Approval Teknis/SPV
            $table->unsignedBigInteger('approved_tech_by')->nullable()->after('status');
            $table->timestamp('approved_tech_at')->nullable()->after('approved_tech_by');

            // Kolom untuk Approval GA (Persiapan ke depan)
            $table->unsignedBigInteger('approved_ga_by')->nullable()->after('approved_tech_at');
            $table->timestamp('approved_ga_at')->nullable()->after('approved_ga_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_general_affairs', function (Blueprint $table) {
            //
        });
    }
};
