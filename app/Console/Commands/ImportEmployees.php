<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Support\Facades\Hash;

class ImportEmployees extends Command
{
    protected $signature = 'employee:import {file}';
    protected $description = 'Import data karyawan dengan Mapping Divisi & Jabatan';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("❌ File tidak ditemukan di: $filePath");
            return;
        }

        // 1. KAMUS MAPPING (UBAH NAMA EXCEL -> NAMA DATABASE)
        // Format: 'NAMA DI EXCEL' => 'NAMA SINGKATAN'
        $divisiMap = [
            'INFORMATION TECHNOLOGY'      => 'IT',
            'PROCESS ENGINEERING'         => 'ENGINEERING',
            'QUALITY ASSURANCE & R D'     => 'QA',
            'SALES SUPPORT'               => 'SS',
            'COMMERCIAL & SUPPLY CHAIN'   => 'SC',
            'HUMAN CAPITAL'               => 'HC',
        ];

        // 2. DAFTAR DIVISI YANG DIIZINKAN MASUK (TARGET)
        // Gunakan nama HASIL SINGKATAN di sini
        $targetDivisi = [
            'PRESIDENT DIRECTOR',
            'GENERAL AFFAIR',
            'IT',             // Sudah disingkat
            'ENGINEERING',    // Sudah disingkat
            'FACILITY',
            'MAINTENANCE',
            'MARKETING',
            'PLANT A',
            'PLANT B',
            'PLANT C',
            'PLANT D',
            'PLANT E',
            'QA',             // Sudah disingkat
            'SALES 1',
            'SALES 2',
            'SS',             // Sudah disingkat
            'SC',             // Sudah disingkat
            'HC',             // Sudah disingkat
        ];

        $this->info("🚀 Memulai proses import...");

        $reader = SimpleExcelReader::create($filePath);
        $this->output->progressStart(100);

        $masuk = 0;
        $skip = 0;

        $reader->getRows()->each(function (array $row) use ($targetDivisi, $divisiMap, &$masuk, &$skip) {

            // A. BERSIHKAN & MAPPING DIVISI
            $rawDivisi = strtoupper(trim($row['Organization']));

            // Cek di kamus: Kalau ada di map pakai singkatan, kalau tidak pakai aslinya
            $fixedDivisi = $divisiMap[$rawDivisi] ?? $rawDivisi;

            // B. FILTER (Hanya izinkan yang ada di daftar target)
            if (!in_array($fixedDivisi, $targetDivisi)) {
                $skip++;
                return; // Lewati baris ini
            }

            // C. SIMPAN KE DATABASE
            User::updateOrCreate(
                ['nik' => $row['Employee ID']], // Kunci Unik
                [
                    'name'         => $row['Full Name'],
                    'divisi'       => $fixedDivisi, // Masukkan nama divisi yang sudah fixed
                    'jabatan' => $row['Job Position'] ?? null,

                    'password'     => Hash::make('jembopass'),
                    'role'         => 'user'
                ]
            );

            $masuk++;
            $this->output->progressAdvance();
        });

        $this->output->progressFinish();
        $this->info("------------------------------------------------");
        $this->info("✅ BERHASIL DISIMPAN : $masuk Karyawan");
        $this->comment("⏭️  DILEWATI (SKIP)   : $skip Baris");
        $this->info("------------------------------------------------");
    }
}
