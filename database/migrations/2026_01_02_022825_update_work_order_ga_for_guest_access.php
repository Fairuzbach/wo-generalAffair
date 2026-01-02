<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_order_general_affairs', function (Blueprint $table) {
            // Tambah kolom NIK
            $table->string('requester_nik')->nullable()->after('ticket_num');

            // Ubah requester_id jadi boleh kosong (Nullable) karena tamu tidak punya ID Login
            $table->unsignedBigInteger('requester_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('work_order_general_affairs', function (Blueprint $table) {
            $table->dropColumn('requester_nik');
            // Kembalikan ke tidak boleh null (opsional, hati-hati jika rollback)
            // $table->unsignedBigInteger('requester_id')->nullable(false)->change();
        });
    }
};
