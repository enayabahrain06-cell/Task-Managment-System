<?php

use Illuminate\Support\Facades\Route;

// Basic Laravel auth routes using built-in controllers
Route::get('/login', '\App\Http\Controllers\Auth\AuthenticatedSessionController@create')->middleware('guest')->name('login');
Route::post('/login', '\App\Http\Controllers\Auth\AuthenticatedSessionController@store')->middleware('guest');
Route::post('/logout', '\App\Http\Controllers\Auth\AuthenticatedSessionController@destroy')->name('logout');

Route::get('/register', '\App\Http\Controllers\Auth\RegisteredUserController@create')->middleware('guest')->name('register');
Route::post('/register', '\App\Http\Controllers\Auth\RegisteredUserController@store')->middleware('guest');

// Password reset routes (disabled for now)
// Route::get('/forgot-password', '\App\Http\Controllers\Auth\PasswordResetLinkController@create')->middleware('guest')->name('password.request');
// Route::post('/forgot-password', '\App\Http\Controllers\Auth\PasswordResetLinkController@store')->middleware('guest')->name('password.email');
// Route::get('/reset-password/{token}', '\App\Http\Controllers\Auth\NewPasswordController@create')->middleware('guest')->name('password.reset');
// Route::post('/reset-password', '\App\Http\Controllers\Auth\NewPasswordController@store')->middleware('guest')->name('password.update');

// MFA routes (require auth but NOT mfa middleware)
Route::middleware(['auth'])->prefix('mfa')->name('mfa.')->group(function () {
    Route::get('/challenge',        [\App\Http\Controllers\MfaController::class, 'challenge'])->name('challenge');
    Route::post('/challenge',       [\App\Http\Controllers\MfaController::class, 'verify'])->name('verify');
    Route::get('/setup',            [\App\Http\Controllers\MfaController::class, 'setup'])->name('setup');
    Route::post('/enable',          [\App\Http\Controllers\MfaController::class, 'enable'])->name('enable');
    Route::post('/disable',         [\App\Http\Controllers\MfaController::class, 'disable'])->name('disable');
    Route::post('/regenerate-codes',[\App\Http\Controllers\MfaController::class, 'regenerateCodes'])->name('regenerate');
    Route::get('/email-recovery',   [\App\Http\Controllers\MfaController::class, 'emailRecovery'])->name('email-recovery');
    Route::post('/email-recovery',  [\App\Http\Controllers\MfaController::class, 'sendEmailCode'])->name('send-email-code');
    Route::post('/verify-email',    [\App\Http\Controllers\MfaController::class, 'verifyEmailCode'])->name('verify-email');
});

