<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Support\Facades\Hash;

class ImportEmployees extends Command
{
    protected $signature = 'employee:import {file}';
    protected $description = 'Import data karyawan dengan Fix NIK & Jabatan';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("❌ File tidak ditemukan di: $filePath");
            return;
        }

        // 1. KAMUS MAPPING DIVISI
        $divisiMap = [
            'INFORMATION TECHNOLOGY'      => 'IT',
            'PROCESS ENGINEERING'         => 'ENGINEERING',
            'QUALITY ASSURANCE & R D'     => 'QR',
            'SALES SUPPORT'               => 'SS',
            'COMMERCIAL & SUPPLY CHAIN'   => 'SC',
            'HUMAN CAPITAL'               => 'HC',
        ];

        // 2. DAFTAR TARGET DIVISI (Gunakan Singkatan)
        $targetDivisi = [
            'PRESIDENT DIRECTOR',
            'GENERAL AFFAIR',
            'IT',
            'ENGINEERING',
            'FACILITY',
            'MAINTENANCE',
            'MARKETING',
            'PLANT A',
            'PLANT B',
            'PLANT C',
            'PLANT D',
            'PLANT E',
            'QR',
            'SALES 1',
            'SALES 2',
            'SS',
            'SC',
            'HC',
        ];

        $this->info("🚀 Memulai proses import...");

        $reader = SimpleExcelReader::create($filePath);
        $this->output->progressStart(100);

        $masuk = 0;
        $skip = 0;

        $reader->getRows()->each(function (array $row) use ($targetDivisi, $divisiMap, &$masuk, &$skip) {

            // A. LOGIKA DIVISI
            $rawDivisi = strtoupper(trim($row['Organization']));
            $fixedDivisi = $divisiMap[$rawDivisi] ?? $rawDivisi;

            if (!in_array($fixedDivisi, $targetDivisi)) {
                $skip++;
                return;
            }

            // B. FIX NIK (LOGIKA BARU)
            $nik = trim((string) $row['Employee ID']);

            // Cek: Apakah NIK ini HANYA ANGKA?
            if (ctype_digit($nik)) {
                // Jika Angka & kurang dari 4 digit, tambahkan 0 di depan
                if (strlen($nik) < 4) {
                    $nik = str_pad($nik, 4, '0', STR_PAD_LEFT);
                }
            }
            // Jika ada huruf (misal: DIR05), dia akan lolos tanpa diubah (tetap DIR05)

            // C. SIMPAN KE DATABASE
            User::updateOrCreate(
                ['nik' => $nik],
                [
                    'name'         => $row['Full Name'],
                    'divisi'       => $fixedDivisi,
                    'jabatan'      => $row['Job Position'] ?? null,
                    'password'     => Hash::make('jembopass'),
                    'role'         => 'user',
                    'is_active'    => true,
                ]
            );

            $masuk++;
            $this->output->progressAdvance();
        });

        $this->output->progressFinish();
        $this->info("------------------------------------------------");
        $this->info("✅ BERHASIL DISIMPAN : $masuk Karyawan");
        $this->info("------------------------------------------------");
    }
}
