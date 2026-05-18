<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\GoogleLinkController;
use App\Http\Controllers\DerivOAuthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::middleware(['auth'])->group(function () {
    Route::get('/auth/google/link', [GoogleLinkController::class, 'redirect'])->name('auth.google.link');
    Route::get('/auth/google/link/callback', [GoogleLinkController::class, 'callback'])->name('auth.google.link.callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => view('pages.dashboard'))->name('dashboard');
    Route::get('/copy-trading', fn () => view('pages.copy-trading'))->name('copy-trading');
    Route::get('/account', fn () => view('pages.account'))->name('account');
    Route::get('/trades', fn () => view('pages.trades'))->name('trades');

    Route::get('/deriv/connect', [DerivOAuthController::class, 'redirect'])->name('deriv.connect');
    Route::get('/deriv/callback', [DerivOAuthController::class, 'callback'])->name('deriv.callback');
    Route::post('/deriv/connect-pat', [DerivOAuthController::class, 'connectPat'])->name('deriv.connect.pat');
    Route::delete('/deriv/disconnect', [DerivOAuthController::class, 'disconnect'])->name('deriv.disconnect');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', fn () => view('pages.admin.dashboard'))->name('admin.dashboard');
    Route::get('/users', fn () => view('pages.admin.users'))->name('admin.users');
    Route::get('/settings', fn () => view('pages.admin.settings'))->name('admin.settings');
});

require __DIR__.'/settings.php';
