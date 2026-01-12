<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GeneralAffair\GeneralAffairController;
use Illuminate\Support\Facades\Route;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ====================================================
// 1. ROUTE PUBLIC (BISA DIAKSES TANPA LOGIN)
// ====================================================

// Halaman Depan
Route::get('/', function () {
    return view('landing');
});

// ====================================================
// 2. ROUTE PROTECTED (WAJIB LOGIN)
// ====================================================
Route::middleware(['auth', 'verified'])->group(function () {

    // --- DASHBOARD UMUM ---
    Route::get('/dashboard', function () {
        return view('landing');
    })->name('dashboard');


    // -----------------------------------------------------------------
    // A. GENERAL AFFAIR - AKSES UMUM (Semua Karyawan)
    // -----------------------------------------------------------------

    // Halaman Index & Create
    Route::get('/general-affair', [GeneralAffairController::class, 'index'])->name('ga.index');
    Route::get('/ga/create', [GeneralAffairController::class, 'create'])->name('ga.create');

    // Kirim Tiket
    Route::post('/ga/store', [GeneralAffairController::class, 'store'])->name('ga.store');

    // API Cek NIK (Ajax)
    Route::get('/ga/check-employee', [GeneralAffairController::class, 'checkEmployee'])->name('ga.check_employee');


    // -----------------------------------------------------------------
    // B. GENERAL AFFAIR - APPROVAL (Manager / SPV / Admin Teknis)
    // -----------------------------------------------------------------

    // Jalur 1: Admin Teknis (MT/FH/ENG)
    Route::match(['get', 'post'], '/wo/approve-technical/{id}', [GeneralAffairController::class, 'approveByTechnical'])->name('wo.approve_technical');

    // Jalur 2: Admin GA
    Route::post('/wo/approve-ga/{id}', [GeneralAffairController::class, 'approveByGA'])
        ->name('wo.approve_ga');
    Route::put('/ga/process/{id}', [GeneralAffairController::class, 'processTicket'])
        ->name('work-order-ga.process');

    // -----------------------------------------------------------------
    // C. GENERAL AFFAIR - KHUSUS ADMIN GA
    // -----------------------------------------------------------------
    // [PERBAIKAN] Kita hapus middleware closure yang bikin error.
    // Keamanan sudah ditangani di dalam Controller masing-masing function.

    Route::group([], function () {

        // Dashboard Statistik
        Route::get('/ga/dashboard', [GeneralAffairController::class, 'dashboard'])->name('ga.dashboard');

        // Export Excel
        Route::get('/ga/export', [GeneralAffairController::class, 'export'])->name('ga.export');

        // Update Status & PIC
        Route::put('/ga/update-status/{id}', [GeneralAffairController::class, 'updateStatus'])->name('ga.update_status');

        Route::get('/get-departments/{plant_id}', [GeneralAffairController::class, 'getDepartmentsByPlant'])
            ->name('get.departments');

        // Reject oleh GA
        Route::post('/ga/reject/{id}', [GeneralAffairController::class, 'reject'])->name('ga.reject');

        // Hapus Data
        Route::delete('/ga/delete/{id}', [GeneralAffairController::class, 'destroy'])->name('ga.destroy');
    });
});


// ====================================================
// 3. PROFILE
// ====================================================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
});

require __DIR__ . '/auth.php';
