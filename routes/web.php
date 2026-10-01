<?php

use App\Http\Controllers\AdminEnrollmentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EnrollmentApplicationController;
use App\Http\Controllers\AdminEnrollmentApplicationController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\PortalAuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentPortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/enrollment', [EnrollmentApplicationController::class, 'create'])->name('enrollment.create');
Route::post('/enrollment/step/{step}', [EnrollmentApplicationController::class, 'saveStep'])->name('enrollment.step');
Route::post('/enrollment/submit', [EnrollmentApplicationController::class, 'submit'])->name('enrollment.submit');
Route::post('/enrollment/cancel', [EnrollmentApplicationController::class, 'cancel'])->name('enrollment.cancel');
Route::get('/enrollment/success/{application}', [EnrollmentApplicationController::class, 'success'])->name('enrollment.success');

// Portal access page
Route::get('/portal/login', [PortalAuthController::class, 'showLogin'])->name('portal.login');

Route::get('/portal/register', [PortalAuthController::class, 'create'])->name('portal.register');
Route::post('/portal/register', [PortalAuthController::class, 'register'])
    ->middleware('throttle:10,1')
    ->name('portal.register.submit');

// Portal access form submission
Route::post('/portal/login', [PortalAuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('portal.login.submit');

// Portal logout route
Route::post('/portal/logout', [PortalAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('portal.logout');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/enrollment-applications', [AdminEnrollmentApplicationController::class, 'index'])->name('admin.enrollment-applications.index');
    Route::get('/admin/enrollment-applications/history', [AdminEnrollmentApplicationController::class, 'history'])->name('admin.enrollment-applications.history');
    Route::get('/admin/enrollment-applications/{application}/edit', [AdminEnrollmentApplicationController::class, 'edit'])->name('admin.enrollment-applications.edit');
    Route::patch('/admin/enrollment-applications/{application}', [AdminEnrollmentApplicationController::class, 'update'])->name('admin.enrollment-applications.update');
    Route::get('/admin/enrollment-applications/{application}', [AdminEnrollmentApplicationController::class, 'show'])->name('admin.enrollment-applications.show');
    Route::post('/admin/enrollment-applications/{application}/process', [AdminEnrollmentApplicationController::class, 'process'])->name('admin.enrollment-applications.process');
    Route::post('/admin/enrollment-applications/{application}/resend-email', [AdminEnrollmentApplicationController::class, 'resend'])->name('admin.enrollment-applications.resend');
    Route::resource('students', StudentController::class);
    Route::post('/students/{student}/archive', [StudentController::class, 'archive'])->name('students.archive');
    Route::post('/students/{student}/enroll', [StudentController::class, 'enroll'])->name('students.enroll');
    Route::post('/students/{student}/courses/{course}/grade', [StudentController::class, 'updateGrade'])
        ->name('students.courses.update-grade');
    Route::resource('courses', CourseController::class)->except(['show', 'destroy']);
    Route::post('/courses/{course}/archive', [CourseController::class, 'archive'])->name('courses.archive');
    Route::resource('departments', DepartmentController::class)->except(['show', 'destroy']);
    Route::post('/departments/{department}/archive', [DepartmentController::class, 'archive'])->name('departments.archive');
    Route::resource('programs', ProgramController::class)->except(['show', 'destroy']);
    Route::post('/programs/{program}/archive', [ProgramController::class, 'archive'])->name('programs.archive');
    Route::get('/admin/enrollments', [AdminEnrollmentController::class, 'index'])->name('admin.enrollments.index');
    Route::get('/admin/enrollments/{enrollment}', [AdminEnrollmentController::class, 'show'])->name('admin.enrollments.show');
    Route::patch('/admin/enrollments/{enrollment}', [AdminEnrollmentController::class, 'update'])->name('admin.enrollments.update');
});

Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentPortalController::class, 'index'])->name('dashboard');
    Route::get('/enrollments/create', [StudentPortalController::class, 'createEnrollment'])->name('enrollments.create');
    Route::post('/enrollments', [StudentPortalController::class, 'storeEnrollment'])->name('enrollments.store');
    Route::get('/enrollments/{enrollment}', [StudentPortalController::class, 'showEnrollment'])->name('enrollments.show');
});
