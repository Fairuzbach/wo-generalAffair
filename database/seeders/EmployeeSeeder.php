<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $employees = [
            // --- ENGINEERING (3 Orang) ---
            [
                'nik' => '5001',
                'name' => 'Budi Santoso',
                'department' => 'Engineering',
                'position' => 'Supervisor',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'nik' => '5002',
                'name' => 'Andi Pratama',
                'department' => 'Engineering',
                'position' => 'Staff',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'nik' => '5003',
                'name' => 'Citra Lestari',
                'department' => 'Engineering',
                'position' => 'Staff',
                'created_at' => $now,
                'updated_at' => $now
            ],

            // --- GENERAL AFFAIR (3 Orang) ---
            [
                'nik' => '6001',
                'name' => 'Dewi Sartika',
                'department' => 'General Affair',
                'position' => 'Supervisor',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'nik' => '6002',
                'name' => 'Eko Purnomo',
                'department' => 'General Affair',
                'position' => 'Staff',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'nik' => '6003',
                'name' => 'Fajar Nugraha',
                'department' => 'General Affair',
                'position' => 'Staff',
                'created_at' => $now,
                'updated_at' => $now
            ],

            // --- MAINTENANCE (4 Orang) ---
            [
                'nik' => '7001',
                'name' => 'Gilang Ramadhan',
                'department' => 'Maintenance',
                'position' => 'Supervisor',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'nik' => '7002',
                'name' => 'Hendra Wijaya',
                'department' => 'Maintenance',
                'position' => 'Operator',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'nik' => '7003',
                'name' => 'Iwan Setiawan',
                'department' => 'Maintenance',
                'position' => 'Operator',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'nik' => '7004',
                'name' => 'Joko Susilo',
                'department' => 'Maintenance',
                'position' => 'Operator',
                'created_at' => $now,
                'updated_at' => $now
            ],
        ];

        // Kosongkan tabel dulu agar tidak duplikat (Opsional)
        // DB::table('employees')->truncate(); 

        DB::table('employees')->insert($employees);
    }
}
