<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\StudentRegistrationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\SocialController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\ImpersonationController;

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

    // Social Authentication routes
    Route::get('/auth/{provider}', [SocialController::class, 'redirectToProvider'])
        ->where('provider', 'google|microsoft')
        ->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialController::class, 'handleProviderCallback'])
        ->where('provider', 'google|microsoft')
        ->name('social.callback');
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

    // Dashboard route (requires email verification)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(['verified'])
        ->name('dashboard');

    // Student Management Routes
    Route::resource('students', StudentController::class)
        ->middleware(['verified']);

    // Additional student routes
    Route::post('/students/{student}/restore', [StudentController::class, 'restore'])
        ->name('students.restore')
        ->middleware(['verified']);
    Route::delete('/students/{student}/force-delete', [StudentController::class, 'forceDelete'])
        ->name('students.force-delete')
        ->middleware(['verified']);
    Route::post('/students/bulk-action', [StudentController::class, 'bulkAction'])
        ->name('students.bulk-action')
        ->middleware(['verified']);

    // Student Registration (Multi-step) Routes
    Route::prefix('students/registration')->name('students.registration.')->middleware(['verified'])->group(function () {
        Route::get('/', [StudentRegistrationController::class, 'index'])->name('index');
        Route::get('/step1', [StudentRegistrationController::class, 'step1'])->name('step1');
        Route::post('/step1', [StudentRegistrationController::class, 'processStep1'])->name('process-step1');
        Route::get('/step2', [StudentRegistrationController::class, 'step2'])->name('step2');
        Route::post('/step2', [StudentRegistrationController::class, 'processStep2'])->name('process-step2');
        Route::get('/step3', [StudentRegistrationController::class, 'step3'])->name('step3');
        Route::post('/step3', [StudentRegistrationController::class, 'processStep3'])->name('process-step3');
        Route::get('/step4', [StudentRegistrationController::class, 'step4'])->name('step4');
        Route::post('/step4', [StudentRegistrationController::class, 'processStep4'])->name('process-step4');
        Route::get('/review', [StudentRegistrationController::class, 'review'])->name('review');
        Route::post('/restart', [StudentRegistrationController::class, 'restart'])->name('restart');
    });

    // Student Profile Management Routes
    Route::prefix('students/{student}/profile')->name('students.profile.')->middleware(['verified'])->group(function () {
        Route::get('/', [StudentProfileController::class, 'show'])->name('show');
        Route::get('/edit', [StudentProfileController::class, 'edit'])->name('edit');
        Route::put('/update', [StudentProfileController::class, 'update'])->name('update');
        Route::get('/medical', [StudentProfileController::class, 'medical'])->name('medical');
        Route::get('/academic', [StudentProfileController::class, 'academic'])->name('academic');
        Route::get('/documents', [StudentProfileController::class, 'documents'])->name('documents');
        Route::post('/documents/upload', [StudentProfileController::class, 'uploadDocuments'])->name('documents.upload');
        Route::delete('/documents/delete', [StudentProfileController::class, 'deleteDocument'])->name('documents.delete');
        Route::get('/contacts', [StudentProfileController::class, 'contacts'])->name('contacts');
        Route::get('/print', [StudentProfileController::class, 'print'])->name('print');
    });

    // Student Search and Export Routes (Issue #35)
    Route::prefix('students')->name('students.')->middleware(['verified'])->group(function () {
        Route::get('/search', [StudentController::class, 'search'])->name('search');
        Route::get('/search-options', [StudentController::class, 'searchOptions'])->name('search.options');
        Route::get('/export', [StudentController::class, 'export'])->name('export');
    });

    // Student Bulk Operations Routes (Issue #36)
    Route::prefix('students/bulk')->name('students.bulk.')->middleware(['verified'])->group(function () {
        Route::post('/import-csv', [StudentController::class, 'importCsv'])->name('import.csv');
        Route::post('/status-update', [StudentController::class, 'bulkStatusUpdate'])->name('status.update');
        Route::post('/class-assignment', [StudentController::class, 'bulkClassAssignment'])->name('class.assignment');
        Route::post('/communication', [StudentController::class, 'massCommunication'])->name('communication');
        Route::post('/export', [StudentController::class, 'bulkExport'])->name('export');
        Route::get('/job-progress/{jobId}', [StudentController::class, 'getJobProgress'])->name('job.progress');
    });

    // Student Document Management Routes (Issue #37)
    Route::prefix('students/{student}/documents')->name('documents.')->middleware(['verified'])->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('/categories', [DocumentController::class, 'categories'])->name('categories');
        Route::get('/upload-progress', [DocumentController::class, 'uploadProgress'])->name('upload.progress');
    });

    Route::prefix('documents')->name('documents.')->middleware(['verified'])->group(function () {
        Route::get('/{document}', [DocumentController::class, 'show'])->name('show');
        Route::put('/{document}', [DocumentController::class, 'update'])->name('update');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
        Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
        Route::post('/{document}/version', [DocumentController::class, 'createVersion'])->name('version.create');
        Route::post('/{document}/verify', [DocumentController::class, 'verify'])->name('verify');
        Route::post('/{document}/archive', [DocumentController::class, 'archive'])->name('archive');
        Route::post('/{document}/restore', [DocumentController::class, 'restore'])->name('restore');
    });

    // Document Bulk Operations Routes (Issue #37)
    Route::prefix('documents/bulk')->name('documents.bulk.')->middleware(['verified'])->group(function () {
        Route::post('/upload', [DocumentController::class, 'bulkUpload'])->name('upload');
        Route::post('/categorize', [DocumentController::class, 'bulkCategorize'])->name('categorize');
        Route::post('/delete', [DocumentController::class, 'bulkDelete'])->name('delete');
        Route::post('/archive', [DocumentController::class, 'bulkArchive'])->name('archive');
        Route::post('/download', [DocumentController::class, 'bulkDownload'])->name('download');
        Route::get('/download/{filename}', [DocumentController::class, 'downloadBulkFile'])->name('download.file');
        Route::get('/stats', [DocumentController::class, 'bulkStats'])->name('stats');
    });

    // Report Management Routes (Issue #38)
    Route::prefix('reports')->name('reports.')->middleware(['verified'])->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::post('/', [ReportController::class, 'store'])->name('store');
        Route::get('/templates', [ReportController::class, 'templates'])->name('templates');
        Route::get('/statistics', [ReportController::class, 'statistics'])->name('statistics');
        Route::post('/bulk-export', [ReportController::class, 'bulkExport'])->name('bulk-export');
        
        // Specialized report endpoints
        Route::get('/attendance', [ReportController::class, 'attendanceReports'])->name('attendance');
        Route::get('/academic', [ReportController::class, 'academicReports'])->name('academic');
        
        // Individual report routes
        Route::get('/{report}', [ReportController::class, 'show'])->name('show');
        Route::put('/{report}', [ReportController::class, 'update'])->name('update');
        Route::delete('/{report}', [ReportController::class, 'destroy'])->name('destroy');
        Route::post('/{report}/generate', [ReportController::class, 'generate'])->name('generate');
        Route::post('/{report}/export', [ReportController::class, 'export'])->name('export');
        Route::get('/{report}/download', [ReportController::class, 'download'])->name('download');
    });

    // Admin routes (requires admin role)
    Route::middleware(['role:admin|super_admin'])->prefix('admin')->name('admin.')->group(function () {
        // User impersonation routes
        Route::post('/impersonate/{user}', [ImpersonationController::class, 'impersonate'])->name('impersonate');
        Route::delete('/stop-impersonating', [ImpersonationController::class, 'stopImpersonating'])->name('stop-impersonating');
    });
});

// Two Factor Authentication Challenge routes (for partially authenticated users)
Route::middleware(['auth', 'throttle:5,1'])->group(function () {
    Route::get('/two-factor-challenge', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
});
