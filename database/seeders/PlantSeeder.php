<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Engineering\Plant;

class PlantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            'Plant A',
            'Plant B',
            'Plant C',
            'Plant D',
            'Plant E',
            'Plant F',
            'QC FO',
            'HC',
            'FA',
            'IT',
            'Sales',
            'Marketing',
            'RM Office',
            'RM 1',
            'RM 2',
            'RM 3',
            'RM 5',
            'SC',
            'QC LAB',
            'QC MV',
            'QC LV',
            'MC Cable',
            'Autowire',
            'Workshop Electric',
            'Konstruksi',
            'Gudang Jadi',
            'Plant Tools',
        ];
        foreach ($locations as $loc) {
            Plant::firstOrCreate(['name' => $loc], ['name' => $loc]);
        }
    }
}
