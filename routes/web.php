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

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/user-target/{user}', [DashboardController::class, 'updateUserTarget'])->name('dashboard.user-target.update');
    Route::resource('companies', CompanyController::class)->only([
        'index',
        'create',
        'store',
        'show',
        'edit',
        'update',
    ]);
    Route::post('/companies/{company}/quick-status', [CompanyController::class, 'quickStatus'])->name('companies.quick-status');
    Route::match(['get', 'post'], '/companies/{company}/quick-defer', [CompanyController::class, 'quickDefer'])->name('companies.quick-defer');
    Route::get('/companies-next/mine', [CompanyController::class, 'nextMine'])->name('companies.next-mine');
    Route::resource('calls', CallController::class)->only([
        'index',
        'create',
        'store',
        'show',
        'edit',
        'update',
    ]);
    Route::match(['get', 'post'], '/companies/{company}/start-call', [CallController::class, 'quickStart'])->name('companies.calls.start');
    Route::get('/calls/{call}/finish', [CallController::class, 'finish'])->name('calls.finish');
    Route::resource('follow-ups', FollowUpController::class)->only([
        'index',
        'create',
        'store',
        'show',
        'edit',
        'update',
    ]);
    Route::post('/follow-ups/bulk-complete', [FollowUpController::class, 'bulkComplete'])->name('follow-ups.bulk-complete');
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

    // Breeze profile routes can stay alongside CRM modules.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

if (file_exists(__DIR__.'/auth.php')) {
    require __DIR__.'/auth.php';
}
