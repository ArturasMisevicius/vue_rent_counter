<?php

use App\Livewire\Shell\LogoutEndpoint;
use Illuminate\Support\Facades\Route;

Route::post('/logout', [LogoutEndpoint::class, 'logout'])->name('logout');
