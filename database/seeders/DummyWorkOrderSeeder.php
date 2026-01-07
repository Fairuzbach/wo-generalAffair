<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GeneralAffair\WorkOrderGeneralAffair;

class DummyWorkOrderSeeder extends Seeder
{
    public function run()
    {
        // Kita hanya membuat 25 tiket baru yang MENUNGGU APPROVAL SPV.
        // Status ini aman karena belum membutuhkan kolom 'approved_tech_by'.
        WorkOrderGeneralAffair::factory()->count(25)->waitingSpv()->create();

        $this->command->info('Berhasil membuat 25 tiket dummy dengan status Waiting SPV!');
    }
}
