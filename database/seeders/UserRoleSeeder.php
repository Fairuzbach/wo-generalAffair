<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    public function run()
    {
        // 1. User Umum
        User::updateOrCreate(
            ['email' => 'user@jembo.com'],
            [
                'name' => 'User',
                'nik' => '9999', // Benar
                'password' => Hash::make('password'),
                'role' => 'user',
                'divisi' => 'General'
            ]
        );

        // 2. GA Admin
        User::updateOrCreate(
            ['email' => 'ga@jembo.com'],
            [
                'name' => 'Admin GA',
                'nik' => '9001',            // <--- GANTI JADI NIK DUMMY
                'password' => Hash::make('password'),
                'role' => 'ga.admin',
                'divisi' => 'General Affair'
            ]
        );

        // 3. Engineer Admin
        User::updateOrCreate(
            ['email' => 'engineer@jembo.com'],
            [
                'name' => 'Admin Engineer',
                'nik' => '9002',                  // <--- GANTI JADI NIK DUMMY
                'password' => Hash::make('password'),
                'role' => 'eng.admin',
                'divisi' => 'Engineering'
            ]
        );

        // 4. Facility Admin
        User::updateOrCreate(
            ['email' => 'facility@jembo.com'],
            [
                'name' => 'Admin Facility',
                'nik' => '9003',                  // <--- GANTI JADI NIK DUMMY
                'password' => Hash::make('password'),
                'role' => 'fh.admin',
                'divisi' => 'Facility'
            ]
        );

        // 5. Maintenance Admin
        User::updateOrCreate(
            ['email' => 'maintenance@jembo.com'],
            [
                'name' => 'Admin Maintenance',
                'nik' => '9004',            // <--- GANTI JADI NIK DUMMY
                'password' => Hash::make('password'),
                'role' => 'mt.admin',
                'divisi' => 'Maintenance'
            ]
        );
    }
}
