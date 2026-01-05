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
            // Menambahkan kolom alasan penolakan setelah status_permintaan
            // Nullable karena kalau statusnya Approve/Pending, ini kosong.
            $table->text('rejection_reason')->nullable()->after('status_permintaan');
        });
    }

    public function down()
    {
        Schema::table('work_order_general_affairs', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
