<?php

use App\Http\Controllers\CheckInController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia\Inertia::render('Welcome', ['canRegister' => true]);
})->name('welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('reports', \App\Http\Controllers\ReportsController::class)->name('reports');

    Route::get('classes', [\App\Http\Controllers\ClassController::class, 'index'])->name('classes.index');
    Route::post('classes', [\App\Http\Controllers\ClassController::class, 'store'])->name('classes.store');
    Route::put('classes/{class}', [\App\Http\Controllers\ClassController::class, 'update'])->name('classes.update');
    Route::delete('classes/{class}', [\App\Http\Controllers\ClassController::class, 'destroy'])->name('classes.destroy');
    Route::post('classes/{class}/bookings', [\App\Http\Controllers\ClassBookingController::class, 'store'])->name('classes.bookings.store');
    Route::delete('bookings/{booking}', [\App\Http\Controllers\ClassBookingController::class, 'destroy'])->name('bookings.destroy');

    Route::get('members/search', [MemberController::class, 'search'])->name('members.search');
    Route::resource('members', MemberController::class)->only(['index', 'store', 'show', 'update']);
    Route::resource('plans', PlanController::class)->only(['index', 'store', 'update']);

    Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::post('subscriptions/{subscription}/freeze', [SubscriptionController::class, 'freeze'])->name('subscriptions.freeze');
    Route::post('subscriptions/{subscription}/unfreeze', [SubscriptionController::class, 'unfreeze'])->name('subscriptions.unfreeze');
    Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('subscriptions/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('subscriptions.renew');

    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

    Route::get('check-in', [CheckInController::class, 'index'])->name('checkins.index');
    Route::post('check-in', [CheckInController::class, 'store'])->name('checkins.store');

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
    Route::delete('staff/{user}', [StaffController::class, 'destroy'])->name('staff.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('locale', \App\Http\Controllers\LocaleController::class)->name('locale');
Route::get('card/{token}', \App\Http\Controllers\CardController::class)->name('card');

require __DIR__.'/auth.php';
