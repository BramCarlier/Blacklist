<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlacklistEntryController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1');
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('blacklist', BlacklistEntryController::class)->except(['show']);
    Route::post('blacklist/check', [BlacklistEntryController::class, 'check'])->name('blacklist.check');
    Route::post('blacklist/{blacklistEntry}/status', [BlacklistEntryController::class, 'status'])->name('blacklist.status');
});
