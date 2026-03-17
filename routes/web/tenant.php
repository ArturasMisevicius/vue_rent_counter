<?php

use App\Livewire\Tenant\HomePage;
use App\Livewire\Tenant\InvoiceHistoryPage as TenantInvoiceHistoryPage;
use App\Livewire\Tenant\ProfilePage as TenantProfilePage;
use App\Livewire\Tenant\PropertyPage as TenantPropertyPage;
use App\Livewire\Tenant\ReadingCreatePage;
use App\Livewire\Tenant\UpdatePasswordEndpoint;
use App\Livewire\Tenant\UpdateProfileEndpoint;
use Illuminate\Support\Facades\Route;

Route::livewire('/home', HomePage::class)->name('home');
Route::livewire('/readings/create', ReadingCreatePage::class)->name('readings.create');
Route::livewire('/invoices', TenantInvoiceHistoryPage::class)->name('invoices.index');
Route::get('/invoices/{invoice}/download', [TenantInvoiceHistoryPage::class, 'download'])->name('invoices.download');
Route::livewire('/property', TenantPropertyPage::class)->name('property.show');
Route::livewire('/profile', TenantProfilePage::class)->name('profile.edit');
Route::put('/profile', [UpdateProfileEndpoint::class, 'update'])->name('profile.update');
Route::put('/profile/password', [UpdatePasswordEndpoint::class, 'update'])->name('profile.password.update');
