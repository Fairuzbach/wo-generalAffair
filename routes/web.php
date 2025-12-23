<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Engineering\WorkOrderEngineeringController;
use App\Http\Controllers\GeneralAffair\GeneralAffairController;
use App\Http\Controllers\Facilities\FacilitiesController;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

// routes/web.php

Route::middleware(['auth', 'verified'])->group(function () {

    // Route Dashboard sebagai Landing Page
    Route::get('/dashboard', function () {
        return view('landing');
    })->name('dashboard');

    // Route General Affair
    Route::get('/general-affair', [GeneralAffairController::class, 'index'])
        ->name('ga.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});



require __DIR__ . '/auth.php';
