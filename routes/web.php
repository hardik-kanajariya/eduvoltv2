<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\TwoFactorController;

Route::get('/', function () {
    return view('welcome');
});

// Health check endpoint
Route::get('/health', [HealthController::class, 'index'])->name('health.check');

// Authentication Routes
Route::middleware('guest')->group(function () {
    // Login routes
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    // Registration routes
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // Password reset routes with rate limiting
    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,1') // Limit to 5 attempts per minute
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:5,1') // Limit to 5 attempts per minute
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    // Logout route
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Email verification routes
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Two Factor Authentication routes
    Route::get('/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/two-factor', [TwoFactorController::class, 'store'])->name('two-factor.store');
    Route::delete('/two-factor', [TwoFactorController::class, 'destroy'])->name('two-factor.destroy');
    Route::post('/two-factor/recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->name('two-factor.recovery-codes');
    Route::get('/two-factor/qr-code', [TwoFactorController::class, 'qrCode'])->name('two-factor.qr-code');

    // Dashboard route (requires email verification and tenant)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(['verified', 'tenant'])
        ->name('dashboard');
});

// Two Factor Authentication Challenge routes (for partially authenticated users)
Route::middleware(['auth', 'throttle:5,1'])->group(function () {
    Route::get('/two-factor-challenge', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
});
