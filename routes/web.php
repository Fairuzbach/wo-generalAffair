<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GeneralAffair\GeneralAffairController;
use Illuminate\Support\Facades\Route;

// ====================================================
// 1. ROUTE PUBLIC (BISA DIAKSES TAMU / TANPA LOGIN)
// ====================================================

// Halaman Depan (Landing Page)
Route::get('/', function () {
    return view('landing');
});

// PINTU MASUK FORM GA (PENTING: Pindahkan ini ke sini agar Tamu bisa masuk)
Route::get('/general-affair', [GeneralAffairController::class, 'index'])
    ->name('ga.index');

// API Cek NIK (Harus Public)
Route::post('/check-employee', [GeneralAffairController::class, 'checkEmployee'])
    ->name('ga.check-employee');

// Kirim Laporan (Harus Public)
Route::post('/ga/store', [GeneralAffairController::class, 'store'])
    ->name('ga.store');


// ====================================================
// 2. ROUTE PROTECTED (HARUS LOGIN: ADMIN/STAFF/SPV)
// ====================================================

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard Utama
    Route::get('/dashboard', function () {
        return view('landing');
    })->name('dashboard');

    // --- GENERAL AFFAIR WORKFLOW ROUTES ---

    // [PENTING] Route Approval & Rejection (Digabung disini biar rapi)
    Route::post('/ga/approve/{id}', [GeneralAffairController::class, 'approve'])->name('ga.approve');
    Route::post('/ga/reject/{id}', [GeneralAffairController::class, 'reject'])->name('ga.reject'); // <--- INI BARU DITAMBAHKAN

    // Dashboard Statistik GA
    Route::get('/ga/dashboard', [GeneralAffairController::class, 'dashboard'])->name('ga.dashboard');

    // Export Excel
    Route::get('/ga/export', [GeneralAffairController::class, 'export'])->name('ga.export');

    // Update Status Manual (Edit Modal)
    Route::put('/ga/{id}/update-status', [GeneralAffairController::class, 'updateStatus'])->name('ga.update_status');

    // Hapus Data
    Route::delete('/ga/delete/{id}', [GeneralAffairController::class, 'destroy'])->name('ga.destroy');
});

// Profile Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
