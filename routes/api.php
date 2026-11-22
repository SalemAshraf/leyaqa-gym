<?php

use App\Http\Controllers\Api\MemberApiController;
use Illuminate\Support\Facades\Route;

// Member app API: Bearer = the member's private portal token.
Route::middleware(['member.token', 'throttle:60,1', 'private.headers'])->prefix('member')->name('api.member.')->group(function () {
    Route::get('profile', [MemberApiController::class, 'profile'])->name('profile');
    Route::get('classes', [MemberApiController::class, 'classes'])->name('classes');
    Route::get('bookings', [MemberApiController::class, 'bookings'])->name('bookings');
    Route::post('bookings', [MemberApiController::class, 'book'])->name('book');
    Route::delete('bookings/{bookingId}', [MemberApiController::class, 'cancel'])->name('cancel');
});
