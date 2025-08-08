<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

use Illuminate\Http\Request;

Route::post('/attendance', function (Request $request) {
    $data = $request->validate([
        'user_id' => 'required|integer',
        'fingerprint_hash' => 'required|string',
        'scanned_at' => 'required|date',
    ]);

    \App\Models\Attendance::create($data);

    return response()->json(['message' => 'Attendance recorded']);
});


Route::get('/', function () {
    return view('welcome');
})->name('home');


Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'role:admin'])->group(function () {
    // Route::get('/admin-dashboard', AdminDashboard::class);
    Volt::route('admin/serviceshare', 'DoctorServiceShareManagementComponent')->name('admin.serviceshare');
    Volt::route('admin/doctorsmanagment', 'DoctorsManagementComponent')->name('admin.doctors');
    Volt::route('admin/servicesmanagment', 'ServicesManagementComponent')->name('admin.services');
    Volt::route('endofshift', 'endOfShift')->name('end');
    Volt::route('admin/labtests', 'LabTestsManager')->name('admin.labtests');
    Volt::route('admin/labtestsshare', 'LabTestsShareManager')->name('admin.labtestsshare');
    Volt::route('tinker', 'freeTinker')->name('tinker');





});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    // Volt::route('admin', 'AdminComponent')->name('admin');
    Volt::route('reception', 'Reception')->name('reception');
    Volt::route('labentry', 'LabEntry')->name('labentry');
    Volt::route('case', 'casesCrud')->name('case');
    Volt::route('payout', 'doctorsPayout')->name('payout');
    Volt::route('expence', 'expence')->name('expence');
    Volt::route('showtransactions', 'showTransactions')->name('showtrans');
    Volt::route('case/view/{id}', 'casesView')->name('caseview');
    Volt::route('bookings', 'bookings')->name('bookings');

});

require __DIR__.'/auth.php';
