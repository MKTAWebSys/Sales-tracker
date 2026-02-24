<?php

use App\Http\Controllers\CallController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LeadTransferController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('crm.landing');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('companies', CompanyController::class)->only([
        'index',
        'create',
        'store',
        'show',
        'edit',
        'update',
    ]);

    Route::resource('calls', CallController::class)->only([
        'index',
        'create',
        'store',
        'show',
        'edit',
        'update',
    ]);

    Route::resource('follow-ups', FollowUpController::class)->only([
        'index',
        'create',
        'store',
        'show',
        'edit',
        'update',
    ]);

    Route::get('/imports/xlsx', [ImportController::class, 'xlsx'])->name('imports.xlsx');
    Route::resource('lead-transfers', LeadTransferController::class)->only([
        'index',
        'create',
        'store',
        'show',
        'edit',
        'update',
    ]);

    Route::resource('meetings', MeetingController::class)->only([
        'index',
        'create',
        'store',
        'show',
        'edit',
        'update',
    ]);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
