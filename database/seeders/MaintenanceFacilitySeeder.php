<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MaintenanceFacilitySeeder extends Seeder
{
    public function run()
    {
        // ==========================================
        // 1. ADMIN FACILITY (Role: fh.admin)
        // ==========================================
        User::updateOrCreate(
            ['email' => 'admin.fh@example.com'],
            [
                'name'              => 'Admin Facility',
                'nik'               => '4001',          // NIK 4 Angka
                'divisi'            => 'Facility',
                'role'              => 'fh.admin',      // Role Khusus Admin Facility
                'password'          => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // ==========================================
        // 2. USER BIASA - DIVISI FACILITY (Role: user)
        // ==========================================
        User::updateOrCreate(
            ['email' => 'staff.fh@example.com'],
            [
                'name'              => 'Staff Facility',
                'nik'               => '4002',          // NIK 4 Angka
                'divisi'            => 'Facility',
                'role'              => 'user',          // Role User Biasa
                'password'          => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // ==========================================
        // 3. USER BIASA - DIVISI MAINTENANCE (Role: user)
        // ==========================================
        User::updateOrCreate(
            ['email' => 'manager.mt@example.com'],
            [
                'name'              => 'Manager Maintenance',
                'nik'               => '8801',          // NIK 4 Angka
                'divisi'            => 'Maintenance',
                'role'              => 'mt.admin',          // Role User Biasa
                'password'          => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
    }
}
