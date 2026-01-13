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
            $table->text('completion_note')->nullable()->after('actual_completion_date');
            $table->text('cancellation_note')->nullable()->after('completion_note');
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
