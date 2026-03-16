<?php

use App\Http\Controllers\Auth\EmailLoginController;
use App\Http\Middleware\EnsureMaster;
use App\Http\Middleware\EnsureNotMaster;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login', 302)->name('home');

Route::middleware(['guest'])->group(function () {
    Route::post('/login/email', [EmailLoginController::class, 'sendCode'])
        ->middleware('throttle:login-code')
        ->name('login.email.send');

    Route::get('/login/verify', [EmailLoginController::class, 'create'])
        ->name('login.verify');

    Route::post('/login/verify', [EmailLoginController::class, 'verifyCode'])
        ->middleware('throttle:login-code-verification')
        ->name('login.verify.store');

    Route::post('/login/verify/resend', [EmailLoginController::class, 'resendCode'])
        ->middleware('throttle:login-code')
        ->name('login.verify.resend');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::shopping-list')
        ->middleware(EnsureNotMaster::class)
        ->name('dashboard');
    Route::livewire('full-shopping-list', 'pages::full-shopping-list')
        ->middleware(EnsureNotMaster::class)
        ->name('shopping-list.full');
    Route::livewire('master', 'pages::master-access')
        ->middleware(EnsureMaster::class)
        ->name('master.index');
});

require __DIR__.'/settings.php';
