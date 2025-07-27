<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    // Volt::route('admin', 'AdminComponent')->name('admin');
    Volt::route('admin/serviceshare', 'DoctorServiceShareManagementComponent')->name('admin.serviceshare');
    Volt::route('admin/doctorsmanagment', 'DoctorsManagementComponent')->name('admin.doctors');
    Volt::route('admin/servicesmanagment', 'ServicesManagementComponent')->name('admin.services');
    Volt::route('reception', 'Reception')->name('reception');
    Volt::route('admin/labtests', 'LabTestsManager')->name('admin.labtests');
    Volt::route('admin/labtestsshare', 'LabTestsShareManager')->name('admin.labtestsshare');

});

require __DIR__.'/auth.php';
