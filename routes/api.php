<?php

use App\Http\Controllers\Api\MemberApiController;
use Illuminate\Support\Facades\Route;

// Member app API: exchange the single-use link token for a bounded session.
Route::post('member/session', [MemberApiController::class, 'createSession'])->middleware('throttle:10,1')->name('api.member.session');

// Member app API: Bearer = a portal session token.
Route::middleware(['member.token', 'throttle:60,1', 'private.headers'])->prefix('member')->name('api.member.')->group(function () {
    Route::get('profile', [MemberApiController::class, 'profile'])->name('profile');
    Route::get('classes', [MemberApiController::class, 'classes'])->name('classes');
    Route::get('bookings', [MemberApiController::class, 'bookings'])->name('bookings');
    Route::post('bookings', [MemberApiController::class, 'book'])->name('book');
    Route::delete('bookings/{bookingId}', [MemberApiController::class, 'cancel'])->name('cancel');
});
