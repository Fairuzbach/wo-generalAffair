<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FacilityTech; // <--- JANGAN LUPA IMPORT INI
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(ImprovementParameterSeeder::class);
        $this->call(engineerTechSeeder::class);
        $this->call(ImprovementStatusSeeder::class);
        $this->call(UserRoleSeeder::class);
        $this->call(PlantMachineSeeder::class);

        // 2. DATA ENGINEER (MT) - Masuk ke USERS
        $engineers = [
            'ABDUL HALID ANDRIYANTO',
            'ADI SUANDRI',
            'ADITYA RAMADHAN',
            'ANDY APRIADI',
            'CHRISTIAN BAYU A S',
            'DAFFA ABDUL AZIZ',
            'DANU MAMLUKAT',
            'DWI HASTUTI',
            'EDY MURTOPO',
            'HASIRI',
            'JOKO PURNOMO',
            'KHOIRUL MUNASYIKIN',
            'MAIDAFITRI DEWI PRIATI',
            'MULYANA',
            'MUHAMMAD ANDRIAN',
            'RAHMAT TAMMU',
            'SUDRANTO PURBA',
            'TEGUH MULYAWAN',
            'TRI WAHYU HIDAYAT',
            'YOSEP FAJAR BAYU KURNIAWAN',
        ];

        $nikCounter = 5000;

        foreach ($engineers as $name) {
            $role = ($name === 'ANDY APRIADI' || $name === 'DANU MAMLUKAT') ? 'admin' : 'user';

            // Buat email dari nama
            $emailKey = Str::lower(str_replace(' ', '.', $name));

            User::create([
                'name' => ucwords(Str::lower($name)),

                // PERBAIKAN: Ganti 'username' menjadi 'nik'
                'nik' => (string) $nikCounter++,

                'email' => $emailKey . '@jembo.com',
                'password' => Hash::make('welcomejembo'),
                'role' => $role,
                'divisi' => 'Engineering',
            ]);
        }

        // 3. DATA TEKNISI FACILITY (FH) - Masuk ke FACILITY_TECHS
        $facilityTechnicians = [
            'SARJANA',
            'MULYONO',
            'AGUS DWI PRIYANTO',
            'IRAWAN',
            'MARIO CHANDRA WIJAYA',
            'RUDI',
            'SARTANA',
            'SUHARYANTO',
            'TEGAR ANDI PRATAMA',
            'WAHYU AJI MARHABAN',
        ];

        foreach ($facilityTechnicians as $name) {
            // Gunakan Model FacilityTech
            FacilityTech::create([
                'name' => ucwords(Str::lower($name)),

            ]);
        }
    }
}
