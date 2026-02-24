<?php

use App\Http\Controllers\CallController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LeadTransferController;
use App\Http\Controllers\MeetingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('crm.landing');
})->name('home');

Route::middleware('auth')->group(function () {
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
    Route::get('/lead-transfers', [LeadTransferController::class, 'index'])->name('lead-transfers.index');
    Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.index');
});

if (file_exists(__DIR__.'/auth.php')) {
    require __DIR__.'/auth.php';
}
