<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Portal access page
Route::get('/portal/login', function () {
    return view('portal.login');
})->name('portal.login');

// Portal access form submission
Route::post('/portal/login', function (Request $request) {
    $credentials = $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    // Both Wrong
    if ($credentials['username'] !== 'admin' && $credentials['password'] !== 'password123') {
        return back()->withErrors([
            'username' => 'Invalid username.',
            'password' => 'Invalid password.',
        ])->withInput($request->only('username'));
    }

    // Username Wrong
    if ($credentials['username'] !== 'admin') {
        return back()->withErrors([
            'username' => 'Invalid Username.',
        ])->withInput($request->only('username'));
    }

    // Password Wrong
    if ($credentials['password'] !== 'password123') {
        return back()->withErrors([
            'password' => 'The password you entered is incorrect.',
        ])->withInput($request->only('username'));
    }

    $request->session()->regenerate();
    $request->session()->put('portal_access', true);

    return redirect()->route('dashboard')->with('success', 'Logged in successfully!');
})->middleware('throttle:5,1')->name('portal.login.submit');

// Portal logout route
Route::post('/portal/logout', function (Request $request) {
    $request->session()->forget('portal_access');
    $request->session()->regenerate();

    return redirect()->route('portal.login')->with('warning', 'You have been logged out.');
})->name('portal.logout');

// Protected student routes + Enroll/Grade route
Route::middleware('portal.access')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('students', StudentController::class);
    Route::post('/students/{student}/enroll', [StudentController::class, 'enroll'])->name('students.enroll');
    Route::post('/students/{student}/courses/{course}/grade', [StudentController::class, 'updateGrade'])
        ->scopeBindings()
        ->name('students.courses.update-grade');
});

Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
