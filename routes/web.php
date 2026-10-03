<?php

use App\Http\Controllers\AdminEnrollmentApplicationController;
use App\Http\Controllers\AdminEnrollmentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentApplicationController;
use App\Http\Controllers\EnrollmentApplicationDocumentController;
use App\Http\Controllers\PortalAuthController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Middleware\EnsureUserRole;
use Illuminate\Support\Facades\Route;

// Public Welcome Page
Route::get('/', fn () => view('welcome'));

// ==========================================
// AUTHENTICATION ROUTES
// ==========================================
Route::get('/student/login', [PortalAuthController::class, 'showStudentLogin'])->name('student.login');
Route::post('/student/login', [PortalAuthController::class, 'studentLogin'])
    ->middleware('throttle:5,1')
    ->name('student.login.submit');

Route::get('/admin/login', [PortalAuthController::class, 'showAdminLogin'])->name('admin.login');
Route::post('/admin/login', [PortalAuthController::class, 'adminLogin'])
    ->middleware('throttle:5,1')
    ->name('admin.login.submit');

Route::get('/portal/register', [PortalAuthController::class, 'create'])->name('portal.register');
Route::post('/portal/register', [PortalAuthController::class, 'register'])
    ->middleware('throttle:5,1')
    ->name('portal.register.submit');

Route::post('/portal/logout', [PortalAuthController::class, 'logout'])->name('portal.logout');

// ==========================================
// PUBLIC ENROLLMENT APPLICATION & CATALOG
// ==========================================
Route::controller(EnrollmentApplicationController::class)->group(function () {
    Route::get('/enrollment', 'create')->name('enrollment.create');
    Route::post('/enrollment/step/{step}', 'saveStep')->whereNumber('step')->name('enrollment.step');
    Route::post('/enrollment/submit', 'submit')->name('enrollment.submit');
    Route::post('/enrollment/cancel', 'cancel')->name('enrollment.cancel');
    Route::get('/enrollment/success/{application}', 'success')->name('enrollment.success');
});

Route::get('/course-catalog', [CourseController::class, 'index'])->name('catalog.courses.index');

// ==========================================
// STUDENT PORTAL (Protected)
// ==========================================
Route::middleware(['auth', 'portal.access', EnsureUserRole::class.':student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/dashboard', [StudentPortalController::class, 'index'])->name('dashboard');
        Route::get('/profile/edit', [StudentPortalController::class, 'editProfile'])->name('profile.edit');
        Route::put('/profile', [StudentPortalController::class, 'updateProfile'])->name('profile.update');
        Route::get('/profile/photo', [StudentPortalController::class, 'profilePhoto'])->name('profile.photo');
        Route::post('/profile/photo', [StudentPortalController::class, 'updateProfilePhoto'])->name('profile.photo.update');
        Route::get('/enrollments/create', [StudentPortalController::class, 'createEnrollment'])->name('enrollments.create');
        Route::post('/enrollments', [StudentPortalController::class, 'storeEnrollment'])->name('enrollments.store');
        Route::get('/enrollments/{enrollment}', [StudentPortalController::class, 'showEnrollment'])->name('enrollments.show');
    });

// ==========================================
// ADMINISTRATOR PORTAL (Protected)
// ==========================================
Route::middleware(['auth', 'portal.access', EnsureUserRole::class.':admin'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Management Resources
    Route::resource('programs', ProgramController::class)->except(['show']);
    Route::post('/programs/{program}/archive', [ProgramController::class, 'archive'])->name('programs.archive');

    Route::resource('courses', CourseController::class)->except(['show']);
    Route::post('/courses/{course}/archive', [CourseController::class, 'archive'])->name('courses.archive');

    // Students Management (Kasama na rito ang index, create, store, show, edit, update, destroy)
    Route::resource('students', StudentController::class);
    Route::post('/students/{student}/enroll', [StudentController::class, 'enroll'])->name('students.enroll');
    Route::post('/students/{student}/courses/{course}/grade', [StudentController::class, 'updateGrade'])
        ->scopeBindings()
        ->name('students.courses.update-grade');

    // Admin Enrollment Applications Management
    Route::get('/admin/enrollment-applications', [AdminEnrollmentApplicationController::class, 'index'])
        ->name('admin.enrollment-applications.index');
    Route::get('/admin/enrollment-applications/history', [AdminEnrollmentApplicationController::class, 'history'])
        ->name('admin.enrollment-applications.history');
    Route::get('/admin/enrollment-applications/{application}/edit', [AdminEnrollmentApplicationController::class, 'edit'])
        ->name('admin.enrollment-applications.edit');
    Route::get('/admin/enrollment-applications/{application}', [AdminEnrollmentApplicationController::class, 'show'])
        ->name('admin.enrollment-applications.show');
    Route::get('/admin/enrollment-applications/{application}/documents/{document}', [EnrollmentApplicationDocumentController::class, 'show'])
        ->scopeBindings()
        ->name('admin.enrollment-applications.documents.show');
    Route::patch('/admin/enrollment-applications/{application}', [AdminEnrollmentApplicationController::class, 'update'])
        ->name('admin.enrollment-applications.update');
    Route::post('/admin/enrollment-applications/{application}/process', [AdminEnrollmentApplicationController::class, 'process'])
        ->name('admin.enrollment-applications.process');
    Route::post('/admin/enrollment-applications/{application}/resend-email', [AdminEnrollmentApplicationController::class, 'resend'])
        ->name('admin.enrollment-applications.resend');

    Route::get('/admin/enrollments', [AdminEnrollmentController::class, 'index'])->name('admin.enrollments.index');
    Route::get('/admin/enrollments/{enrollment}', [AdminEnrollmentController::class, 'show'])->name('admin.enrollments.show');
    Route::post('/admin/enrollments/{enrollment}', [AdminEnrollmentController::class, 'update'])->name('admin.enrollments.update');
});