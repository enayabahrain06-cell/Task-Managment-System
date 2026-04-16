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

