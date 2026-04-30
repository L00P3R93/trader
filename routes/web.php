<?php

use App\Http\Controllers\DerivOAuthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => view('pages.dashboard'))->name('dashboard');
    Route::get('/copy-trading', fn () => view('pages.copy-trading'))->name('copy-trading');

    Route::get('/deriv/connect', [DerivOAuthController::class, 'redirect'])->name('deriv.connect');
    Route::get('/deriv/callback', [DerivOAuthController::class, 'callback'])->name('deriv.callback');
    Route::delete('/deriv/disconnect', [DerivOAuthController::class, 'disconnect'])->name('deriv.disconnect');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users', fn () => view('pages.admin.users'))->name('admin.users');
    Route::get('/settings', fn () => view('pages.admin.settings'))->name('admin.settings');
});

require __DIR__.'/settings.php';
