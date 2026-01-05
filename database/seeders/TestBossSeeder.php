<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class TestBossSeeder extends Seeder
{
    public function run()
    {
        // 1. DATA KARYAWAN (Master Data)
        // HANYA ada 'department', TIDAK ADA 'division'
        $employees = [
            [
                'nik' => '1001',
                'name' => 'Budi Santoso (Manager)',
                'department' => 'Engineering',
                'position' => 'MANAGER ENGINEERING',
            ],
            [
                'nik' => '1002',
                'name' => 'Siti Aminah (Staff)',
                'department' => 'Engineering',
                'position' => 'STAFF ADMIN',
            ],
            [
                'nik' => '2001',
                'name' => 'Agus Kuncoro (SPV)',
                'department' => 'Maintenance',
                'position' => 'SPV MAINTENANCE',
            ],
        ];

        // Masukkan ke tabel employees
        foreach ($employees as $emp) {
            DB::table('employees')->updateOrInsert(
                ['nik' => $emp['nik']],
                $emp // Data ini bersih, tidak ada key 'division'
            );
        }

        // 2. DATA USER (Akun Login)
        // Di sini kita masukkan kolom 'division' ke tabel users
        foreach ($employees as $emp) {
            User::updateOrCreate(
                ['nik' => $emp['nik']],
                [
                    'name' => $emp['name'],
                    'password' => Hash::make('password'),
                    'email' => $emp['nik'] . '@company.com',
                    'role' => 'user', // Default user biasa

                    // KOLOM DIVISION DI TABEL USERS
                    // Kita isi samakan dengan department sesuai permintaan
                    'divisi' => $emp['department'],
                ]
            );
        }
    }
}
