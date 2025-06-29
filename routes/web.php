<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public Pages Routes (Main Website)
Route::name('public.')->group(function () {
    // Home page - accessible via root and /home
    Route::get('/', [App\Http\Controllers\Public\PublicController::class, 'home'])->name('home');
    Route::get('/home', [App\Http\Controllers\Public\PublicController::class, 'home'])->name('home.alt');

    // Competition pages
    Route::get('/competitions', [App\Http\Controllers\Public\PublicController::class, 'competitions'])->name('competitions');
    Route::get('/competition/{slug}', [App\Http\Controllers\Public\PublicController::class, 'competitionDetail'])->name('competition.detail');

    // About and team pages
    Route::get('/about', [App\Http\Controllers\Public\PublicController::class, 'about'])->name('about');
    Route::get('/team', [App\Http\Controllers\Public\PublicController::class, 'team'])->name('team');

    // Testimonials
    Route::get('/testimonials', [App\Http\Controllers\Public\PublicController::class, 'testimonials'])->name('testimonials');
    Route::post('/testimonials', [App\Http\Controllers\Public\PublicController::class, 'storeTestimonial'])->name('testimonials.store');

    // Contact
    Route::get('/contact', [App\Http\Controllers\Public\PublicController::class, 'contact'])->name('contact');
    Route::post('/contact', [App\Http\Controllers\Public\PublicController::class, 'sendContact'])->name('contact.send');

    // Blog and articles
    Route::get('/blog', [App\Http\Controllers\Public\PublicController::class, 'blog'])->name('blog');
    Route::get('/blog/{slug}', [App\Http\Controllers\Public\PublicController::class, 'blogDetail'])->name('blog.detail');

    // Additional public pages
    Route::get('/faq', [App\Http\Controllers\Public\PublicController::class, 'faq'])->name('faq');
    Route::get('/privacy', [App\Http\Controllers\Public\PublicController::class, 'privacy'])->name('privacy');
    Route::get('/terms', [App\Http\Controllers\Public\PublicController::class, 'terms'])->name('terms');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\AuthController::class, 'login']);
    Route::get('/register', [App\Http\Controllers\Auth\AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [App\Http\Controllers\Auth\AuthController::class, 'register']);
    Route::get('/forgot-password', [App\Http\Controllers\Auth\AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [App\Http\Controllers\Auth\AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [App\Http\Controllers\Auth\AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [App\Http\Controllers\Auth\AuthController::class, 'resetPassword'])->name('password.update');
});

// Authenticated Routes
Route::middleware(['auth', 'verified', 'role.redirect'])->group(function () {
    
    // Logout
    Route::post('/logout', [App\Http\Controllers\Auth\AuthController::class, 'logout'])->name('logout');
    
    // Dashboard - Redirect based on role
    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        if ($user->hasRole('Super Admin') || $user->hasRole('Admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('Juri')) {
            return redirect()->route('juri.dashboard');
        } elseif ($user->hasRole('Peserta')) {
            return redirect()->route('peserta.dashboard');
        }
        
        return redirect()->route('login');
    })->name('dashboard');
    
    // Profile Management
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [App\Http\Controllers\Auth\AuthController::class, 'profile'])->name('index');
        Route::put('/', [App\Http\Controllers\Auth\AuthController::class, 'updateProfile'])->name('update');
        Route::put('/password', [App\Http\Controllers\Auth\AuthController::class, 'updatePassword'])->name('password');
    });

    // Super Admin & Admin Routes
    Route::middleware(['role:Super Admin|Admin'])->prefix('admin')->name('admin.')->group(function () {

        // Competition Categories (Super Admin only)
        Route::middleware(['role:Super Admin'])->group(function () {
            Route::resource('competition-categories', App\Http\Controllers\Admin\CompetitionCategoryController::class);
        });

        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/chart-data', [App\Http\Controllers\Admin\DashboardController::class, 'getChartDataAjax'])->name('dashboard.chart-data');
        Route::get('/dashboard/user-distribution', [App\Http\Controllers\Admin\DashboardController::class, 'getUserDistributionAjax'])->name('dashboard.user-distribution');
        Route::get('/dashboard/recent-data', [App\Http\Controllers\Admin\DashboardController::class, 'getRecentDataAjax'])->name('dashboard.recent-data');
        
        // Competition Management
        Route::prefix('competitions')->name('competitions.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\CompetitionController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\CompetitionController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\CompetitionController::class, 'store'])->name('store');
            Route::get('/{competition}', [App\Http\Controllers\Admin\CompetitionController::class, 'show'])->name('show');
            Route::get('/{competition}/edit', [App\Http\Controllers\Admin\CompetitionController::class, 'edit'])->name('edit');
            Route::put('/{competition}', [App\Http\Controllers\Admin\CompetitionController::class, 'update'])->name('update');
            Route::delete('/{competition}', [App\Http\Controllers\Admin\CompetitionController::class, 'destroy'])->name('destroy');
            Route::patch('/{competition}/toggle-status', [App\Http\Controllers\Admin\CompetitionController::class, 'toggleStatus'])->name('toggle-status');
            Route::get('/{competition}/registrations', [App\Http\Controllers\Admin\CompetitionController::class, 'registrations'])->name('registrations');
            Route::get('/{competition}/export', [App\Http\Controllers\Admin\CompetitionController::class, 'export'])->name('export');
        });
        
        // Registration Management
        Route::prefix('registrations')->name('registrations.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\RegistrationController::class, 'index'])->name('index');
            Route::get('/{registration}', [App\Http\Controllers\Admin\RegistrationController::class, 'show'])->name('show');
            Route::patch('/{registration}/confirm', [App\Http\Controllers\Admin\RegistrationController::class, 'confirm'])->name('confirm');
            Route::patch('/{registration}/cancel', [App\Http\Controllers\Admin\RegistrationController::class, 'cancel'])->name('cancel');
            Route::patch('/{registration}/re-enable', [App\Http\Controllers\Admin\RegistrationController::class, 'reEnable'])->name('re-enable');
            Route::delete('/{registration}', [App\Http\Controllers\Admin\RegistrationController::class, 'destroy'])->name('destroy');
            Route::get('/export/excel', [App\Http\Controllers\Admin\RegistrationController::class, 'exportExcel'])->name('export.excel');
            Route::get('/export/pdf', [App\Http\Controllers\Admin\RegistrationController::class, 'exportPdf'])->name('export.pdf');
        });
        
        // Payment Management
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('index');
            Route::get('/{payment}', [App\Http\Controllers\Admin\PaymentController::class, 'show'])->name('show');
            Route::patch('/{payment}/verify', [App\Http\Controllers\Admin\PaymentController::class, 'verify'])->name('verify');
            Route::patch('/{payment}/reject', [App\Http\Controllers\Admin\PaymentController::class, 'reject'])->name('reject');
            Route::patch('/{payment}/confirm', [App\Http\Controllers\Admin\PaymentController::class, 'confirmPayment'])->name('confirm');
        });
        
        // User Management (Super Admin only)
        Route::middleware(['role:Super Admin'])->prefix('users')->name('users.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\UserController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\UserController::class, 'store'])->name('store');
            Route::get('/{user}', [App\Http\Controllers\Admin\UserController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [App\Http\Controllers\Admin\UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [App\Http\Controllers\Admin\UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('destroy');
            Route::patch('/{user}/toggle-status', [App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])->name('toggle-status');
        });

        // Role Management (Super Admin only)
        Route::middleware(['role:Super Admin'])->prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\RoleController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\RoleController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\RoleController::class, 'store'])->name('store');
            Route::get('/{role}', [App\Http\Controllers\Admin\RoleController::class, 'show'])->name('show');
            Route::get('/{role}/edit', [App\Http\Controllers\Admin\RoleController::class, 'edit'])->name('edit');
            Route::put('/{role}', [App\Http\Controllers\Admin\RoleController::class, 'update'])->name('update');
            Route::delete('/{role}', [App\Http\Controllers\Admin\RoleController::class, 'destroy'])->name('destroy');
        });
        
        // Submission Management
        Route::prefix('submissions')->name('submissions.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\SubmissionController::class, 'index'])->name('index');
            Route::get('/{submission}', [App\Http\Controllers\Admin\SubmissionController::class, 'show'])->name('show');
            Route::patch('/{submission}/approve', [App\Http\Controllers\Admin\SubmissionController::class, 'approve'])->name('approve');
            Route::patch('/{submission}/reject', [App\Http\Controllers\Admin\SubmissionController::class, 'reject'])->name('reject');
            Route::delete('/{submission}', [App\Http\Controllers\Admin\SubmissionController::class, 'destroy'])->name('destroy');
        });

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ReportController::class, 'index'])->name('index');
            Route::get('/competitions', [App\Http\Controllers\Admin\ReportController::class, 'competitions'])->name('competitions');
            Route::get('/registrations', [App\Http\Controllers\Admin\ReportController::class, 'registrations'])->name('registrations');
            Route::get('/payments', [App\Http\Controllers\Admin\ReportController::class, 'payments'])->name('payments');
            Route::get('/export/{type}', [App\Http\Controllers\Admin\ReportController::class, 'export'])->name('export');
            Route::get('/competition-distribution', [App\Http\Controllers\Admin\ReportController::class, 'getCompetitionDistribution'])->name('competition-distribution');
            Route::get('/registration-trend', [App\Http\Controllers\Admin\ReportController::class, 'getRegistrationTrend'])->name('registration-trend');
            Route::get('/revenue-trend', [App\Http\Controllers\Admin\ReportController::class, 'getRevenueTrend'])->name('revenue-trend');
        });

        // QR Scanner (Admin & Superadmin only)
        Route::prefix('qr-scanner')->name('qr-scanner.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\QRScannerController::class, 'index'])->name('index');
            Route::post('/verify', [App\Http\Controllers\Admin\QRScannerController::class, 'verify'])->name('verify');
            Route::post('/checkin', [App\Http\Controllers\Admin\QRScannerController::class, 'checkIn'])->name('checkin');
            Route::get('/history', [App\Http\Controllers\Admin\QRScannerController::class, 'history'])->name('history');
        });
        
        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\SettingController::class, 'index'])->name('index');
            Route::put('/', [App\Http\Controllers\Admin\SettingController::class, 'update'])->name('update');
        });
    });

    // Juri Routes
    Route::middleware(['role:Juri'])->prefix('juri')->name('juri.')->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\Juri\JuriDashboardController::class, 'index'])->name('dashboard');
        
        // Assigned Competitions
        Route::prefix('competitions')->name('competitions.')->group(function () {
            Route::get('/', [App\Http\Controllers\Juri\CompetitionController::class, 'index'])->name('index');
            Route::get('/{competition}', [App\Http\Controllers\Juri\CompetitionController::class, 'show'])->name('show');
            Route::get('/{competition}/participants', [App\Http\Controllers\Juri\CompetitionController::class, 'participants'])->name('participants');
        });
        
        // Scoring System
        Route::prefix('scoring')->name('scoring.')->group(function () {
            Route::get('/', [App\Http\Controllers\Juri\ScoringController::class, 'index'])->name('index');
            Route::get('/competition/{competition}', [App\Http\Controllers\Juri\ScoringController::class, 'competition'])->name('competition');
            Route::get('/submission/{submission}', [App\Http\Controllers\Juri\ScoringController::class, 'submission'])->name('submission');
            Route::post('/submission/{submission}', [App\Http\Controllers\Juri\ScoringController::class, 'store'])->name('store');
            Route::get('/participant/{registration}', [App\Http\Controllers\Juri\ScoringController::class, 'participant'])->name('participant');
            Route::post('/score/{registration}', [App\Http\Controllers\Juri\ScoringController::class, 'score'])->name('score');
            Route::put('/score/{score}', [App\Http\Controllers\Juri\ScoringController::class, 'update'])->name('update');
            Route::post('/score/{score}/submit', [App\Http\Controllers\Juri\ScoringController::class, 'submit'])->name('submit');
            Route::post('/finalize/{competition}', [App\Http\Controllers\Juri\ScoringController::class, 'finalize'])->name('finalize');
        });
        
        // Submissions Review
        Route::prefix('submissions')->name('submissions.')->group(function () {
            Route::get('/', [App\Http\Controllers\Juri\SubmissionController::class, 'index'])->name('index');
            Route::get('/{submission}', [App\Http\Controllers\Juri\SubmissionController::class, 'show'])->name('show');
            Route::post('/{submission}/comment', [App\Http\Controllers\Juri\SubmissionController::class, 'addComment'])->name('comment');
        });

        // Export & Reports
        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/scores', [App\Http\Controllers\Juri\ExportController::class, 'exportScores'])->name('scores');
            Route::get('/detailed-report', [App\Http\Controllers\Juri\ExportController::class, 'exportDetailedReport'])->name('detailed-report');
            Route::get('/statistics', [App\Http\Controllers\Juri\ExportController::class, 'exportStatistics'])->name('statistics');
            Route::get('/pdf-report', [App\Http\Controllers\Juri\ExportController::class, 'generatePDFReport'])->name('pdf-report');
        });
    });

    // Peserta Routes
    Route::middleware(['role:Peserta'])->prefix('peserta')->name('peserta.')->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\Peserta\PesertaDashboardController::class, 'index'])->name('dashboard');
        
        // Available Competitions
        Route::prefix('competitions')->name('competitions.')->group(function () {
            Route::get('/', [App\Http\Controllers\Peserta\CompetitionController::class, 'index'])->name('index');
            Route::get('/{competition}', [App\Http\Controllers\Peserta\CompetitionController::class, 'show'])->name('show');
            Route::post('/{competition}/register', [App\Http\Controllers\Peserta\CompetitionController::class, 'register'])->name('register');
        });
        
        // My Registrations
        Route::prefix('registrations')->name('registrations.')->group(function () {
            Route::get('/', [App\Http\Controllers\Peserta\RegistrationController::class, 'index'])->name('index');
            Route::get('/{registration}', [App\Http\Controllers\Peserta\RegistrationController::class, 'show'])->name('show');
            Route::put('/{registration}', [App\Http\Controllers\Peserta\RegistrationController::class, 'update'])->name('update');
            Route::delete('/{registration}', [App\Http\Controllers\Peserta\RegistrationController::class, 'cancel'])->name('cancel');
            Route::get('/{registration}/ticket', [App\Http\Controllers\Peserta\RegistrationController::class, 'ticket'])->name('ticket');
        });
        
        // Submissions
        Route::prefix('submissions')->name('submissions.')->group(function () {
            Route::get('/', [App\Http\Controllers\Peserta\SubmissionController::class, 'index'])->name('index');
            Route::get('/create/{registration}', [App\Http\Controllers\Peserta\SubmissionController::class, 'create'])->name('create');
            Route::post('/{registration}', [App\Http\Controllers\Peserta\SubmissionController::class, 'store'])->name('store');
            Route::get('/{submission}', [App\Http\Controllers\Peserta\SubmissionController::class, 'show'])->name('show');
            Route::get('/{submission}/edit', [App\Http\Controllers\Peserta\SubmissionController::class, 'edit'])->name('edit');
            Route::put('/{submission}', [App\Http\Controllers\Peserta\SubmissionController::class, 'update'])->name('update');
            Route::post('/{submission}/submit', [App\Http\Controllers\Peserta\SubmissionController::class, 'submit'])->name('submit');
            Route::post('/{submission}/upload', [App\Http\Controllers\Peserta\SubmissionController::class, 'uploadFile'])->name('upload');
            Route::delete('/{submission}/file/{filename}', [App\Http\Controllers\Peserta\SubmissionController::class, 'deleteFile'])->name('delete-file');
        });
    });
});

// Payment Routes (Public for callback)
Route::prefix('payment')->name('payment.')->group(function () {
    
    // Payment process for authenticated users
    Route::middleware(['auth'])->group(function () {
        Route::get('/checkout/{registration}', [PaymentController::class, 'checkout'])->name('checkout');
        Route::post('/process/{registration}', [PaymentController::class, 'process'])->name('process');
        Route::get('/status/{payment}', [PaymentController::class, 'status'])->name('status');
        Route::get('/finish/{payment}', [PaymentController::class, 'finish'])->name('finish');
        Route::get('/unfinish/{payment}', [PaymentController::class, 'unfinish'])->name('unfinish');
        Route::get('/error/{payment}', [PaymentController::class, 'error'])->name('error');
        Route::post('/check-status', [PaymentController::class, 'checkStatus'])->name('check-status');
        Route::get('/receipt/{payment}', [PaymentController::class, 'downloadReceipt'])->name('receipt');
    });
    
    // Public callback routes for Midtrans
    Route::post('/notification', [PaymentController::class, 'notification'])->name('notification');
});

// Public Routes for Ticket Verification
Route::prefix('ticket')->name('ticket.')->group(function () {
    Route::get('/verify/{code}', [App\Http\Controllers\TicketController::class, 'verify'])->name('verify');
    Route::get('/scan', [App\Http\Controllers\TicketController::class, 'scan'])->name('scan');
    Route::post('/validate', [App\Http\Controllers\TicketController::class, 'validate'])->name('validate');
});

// Legacy routes for backward compatibility
Route::prefix('public')->group(function () {
    Route::get('/', function () {
        return redirect()->route('public.home');
    });
    Route::get('/competitions', function () {
        return redirect()->route('public.competitions');
    });
    Route::get('/about', function () {
        return redirect()->route('public.about');
    });
    Route::get('/contact', function () {
        return redirect()->route('public.contact');
    });
    Route::get('/testimonials', function () {
        return redirect()->route('public.testimonials');
    });
    Route::get('/blog', function () {
        return redirect()->route('public.blog');
    });
});

// API Routes for AJAX calls
Route::prefix('api')->name('api.')->middleware(['auth'])->group(function () {
    
    // Competition API
    Route::get('/competitions', [App\Http\Controllers\Api\CompetitionController::class, 'index']);
    Route::get('/competitions/{competition}', [App\Http\Controllers\Api\CompetitionController::class, 'show']);
    
    // Registration API
    Route::get('/registrations', [App\Http\Controllers\Api\RegistrationController::class, 'index']);
    Route::get('/registrations/datatable', [App\Http\Controllers\Api\RegistrationController::class, 'datatable']);
    
    // Payment API
    Route::get('/payments', [App\Http\Controllers\Api\PaymentController::class, 'index']);
    Route::get('/payments/datatable', [App\Http\Controllers\Api\PaymentController::class, 'datatable']);
    
    // User API (Admin only)
    Route::middleware(['role:Super Admin|Admin'])->group(function () {
        Route::get('/users', [App\Http\Controllers\Api\UserController::class, 'index']);
        Route::get('/users/datatable', [App\Http\Controllers\Api\UserController::class, 'datatable']);
    });
    
    // Statistics API
    Route::get('/statistics/dashboard', [App\Http\Controllers\Api\StatisticsController::class, 'dashboard']);
    Route::get('/statistics/competition/{competition}', [App\Http\Controllers\Api\StatisticsController::class, 'competition']);
});

// File Download Routes (Authenticated users only)
Route::middleware(['auth'])->prefix('download')->name('download.')->group(function () {
    Route::get('/submission/{submission}/{filename}', [App\Http\Controllers\DownloadController::class, 'submission'])->name('submission');
    Route::get('/payment/{payment}/invoice', [App\Http\Controllers\DownloadController::class, 'invoice'])->name('invoice');
    Route::get('/registration/{registration}/ticket', [App\Http\Controllers\DownloadController::class, 'ticket'])->name('ticket');
});

// Error Pages
Route::get('/403', function () {
    return view('errors.403');
})->name('errors.403');

Route::get('/404', function () {
    return view('errors.404');
})->name('errors.404');

Route::get('/500', function () {
    return view('errors.500');
})->name('errors.500');

// Fallback route for 404
Route::fallback(function () {
    return view('errors.404');
});
