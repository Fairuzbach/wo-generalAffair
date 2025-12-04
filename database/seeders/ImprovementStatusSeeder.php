<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// Sesuaikan namespace ini dengan lokasi Model Anda
use App\Models\Engineering\ImprovementStatus;

class ImprovementStatusSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            'Pending',
            'In Progress',
            'Completed',
            'Cancelled'
        ];

        foreach ($statuses as $status) {
            ImprovementStatus::firstOrCreate(['status' => $status]);
        }
    }
}
