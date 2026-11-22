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

Route::get('/', \App\Http\Controllers\LandingController::class)->name('welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    // Billing stays reachable even when the platform subscription lapses.
    Route::get('billing', [\App\Http\Controllers\BillingController::class, 'show'])->name('billing.show');
    Route::post('billing/subscribe', [\App\Http\Controllers\BillingController::class, 'subscribe'])->name('billing.subscribe');
});

Route::middleware(['auth', 'platform.admin'])->prefix('platform')->name('platform.')->group(function () {
    Route::get('/', [\App\Http\Controllers\PlatformAdminController::class, 'index'])->name('index');
    Route::get('/gyms/{gym}', [\App\Http\Controllers\PlatformAdminController::class, 'show'])->name('gyms.show');
    Route::post('/invoices/{invoice}/paid', [\App\Http\Controllers\PlatformAdminController::class, 'markPaid'])->name('invoices.paid');
    Route::post('/gyms/{gym}/extend-trial', [\App\Http\Controllers\PlatformAdminController::class, 'extendTrial'])->name('gyms.extend-trial');
    Route::post('/gyms/{gym}/status', [\App\Http\Controllers\PlatformAdminController::class, 'setStatus'])->name('gyms.status');
});

Route::middleware(['auth', 'verified', 'platform.active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/setup/dismiss', function () {
        auth()->user()->gym->update(['setup_dismissed_at' => now()]);

        return back();
    })->name('setup.dismiss');
    Route::post('/setup/show', function () {
        auth()->user()->gym->update(['setup_dismissed_at' => null]);

        return back();
    })->name('setup.show');
    Route::get('reports', \App\Http\Controllers\ReportsController::class)->name('reports');

    Route::get('classes', [\App\Http\Controllers\ClassController::class, 'index'])->name('classes.index');
    Route::post('classes', [\App\Http\Controllers\ClassController::class, 'store'])->name('classes.store');
    Route::put('classes/{class}', [\App\Http\Controllers\ClassController::class, 'update'])->name('classes.update');
    Route::delete('classes/{class}', [\App\Http\Controllers\ClassController::class, 'destroy'])->name('classes.destroy');
    Route::post('classes/{class}/bookings', [\App\Http\Controllers\ClassBookingController::class, 'store'])->name('classes.bookings.store');
    Route::delete('bookings/{booking}', [\App\Http\Controllers\ClassBookingController::class, 'destroy'])->name('bookings.destroy');

    Route::get('members/search', [MemberController::class, 'search'])->name('members.search');
    Route::get('leads', [\App\Http\Controllers\LeadController::class, 'index'])->name('leads.index');
    Route::post('leads', [\App\Http\Controllers\LeadController::class, 'store'])->name('leads.store');
    Route::put('leads/{lead}', [\App\Http\Controllers\LeadController::class, 'update'])->name('leads.update');
    Route::delete('leads/{lead}', [\App\Http\Controllers\LeadController::class, 'destroy'])->name('leads.destroy');
    Route::post('leads/{lead}/convert', [\App\Http\Controllers\LeadController::class, 'convert'])->name('leads.convert');
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

    Route::resource('branches', \App\Http\Controllers\BranchController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
    Route::put('staff/{user}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('staff/{user}', [StaffController::class, 'destroy'])->name('staff.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('locale', \App\Http\Controllers\LocaleController::class)->name('locale');
Route::get('card/{token}', \App\Http\Controllers\CardController::class)->name('card');

require __DIR__.'/auth.php';
