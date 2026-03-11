<?php

use App\Http\Middleware\EnsureMaster;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::shopping-list')->name('dashboard');
    Route::livewire('master', 'pages::master-access')
        ->middleware(EnsureMaster::class)
        ->name('master.index');
});

require __DIR__.'/settings.php';
