<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicFeedbackController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TimetableController;
use App\Http\Controllers\Admin\StudentLinkRequestController;
use App\Http\Controllers\Admin\EnrollmentController as AdminEnrollmentController;
use App\Http\Controllers\Admin\QuizController as AdminQuizController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Parent\ChildController;
use App\Http\Controllers\Parent\EnrollmentController;
use App\Http\Controllers\Parent\ChildLearningController;
use App\Http\Controllers\Student\PortalController as StudentPortalController;
use App\Http\Controllers\Student\QuizController as StudentQuizController;
use Illuminate\Support\Facades\Route;

/* Public pages */
Route::get('/', [PublicPageController::class, 'home'])
    ->name('home');

Route::get('/teachers', [PublicPageController::class, 'teachers'])
    ->name('public.teachers');

Route::get('/announcements', [PublicPageController::class, 'announcements'])
    ->name('public.announcements');

Route::get('/feedback', [PublicPageController::class, 'feedback'])
    ->name('public.feedback');

Route::post('/feedback', [PublicFeedbackController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('public.feedback.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.store');

    Route::get('/register', [AuthController::class, 'showRegister'])
        ->name('register');

    Route::post('/register', [AuthController::class, 'register'])
        ->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/* Role dashboards */
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)
            ->name('dashboard');

        Route::get('/enrollments', [AdminEnrollmentController::class, 'index'])
            ->name('enrollments.index');

        Route::resource('subjects', SubjectController::class)
            ->except('show');

        Route::resource('teachers', TeacherController::class)
            ->except('show');

        Route::resource('announcements', AnnouncementController::class)
            ->except('show');

        Route::resource('users', UserController::class)
            ->except('show');

        Route::resource('timetables', TimetableController::class)
            ->except('show');

        Route::resource('packages', PackageController::class)
            ->except('show');

        Route::resource('quizzes', AdminQuizController::class)
            ->except('show');

        Route::get('/student-link-requests', [StudentLinkRequestController::class, 'index'])
            ->name('student-link-requests.index');

        Route::patch('/student-link-requests/{linkRequest}/approve', [StudentLinkRequestController::class, 'approve'])
            ->name('student-link-requests.approve');

        Route::patch('/student-link-requests/{linkRequest}/reject', [StudentLinkRequestController::class, 'reject'])
            ->name('student-link-requests.reject');

        Route::patch('/enrollments/{enrollment}', [AdminEnrollmentController::class, 'update'])
            ->name('enrollments.update');

        Route::patch('/quizzes/{quiz}/publication', [AdminQuizController::class, 'publication'])
            ->name('quizzes.publication');
    });

Route::middleware(['auth', 'role:parent'])
    ->prefix('parent')
    ->name('parent.')
    ->group(function () {
        Route::view('/dashboard', 'parent.dashboard')
            ->name('dashboard');

        Route::get('/children', [ChildController::class, 'index'])
            ->name('children.index');
        
        Route::get('/children/{child}/subjects', [ChildLearningController::class, 'subjects'])
            ->whereNumber('child')
            ->name('children.subjects');

        Route::get('/children/{child}/timetable', [ChildLearningController::class, 'timetable'])
            ->whereNumber('child')
            ->name('children.timetable');

        Route::post('/children', [ChildController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('children.store');

        Route::post('/enrollments', [EnrollmentController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('enrollments.store');

        Route::post('/student-link-requests',[ChildController::class, 'requestLink'])
            ->middleware('throttle:5,1')
            ->name('student-link-requests.store');

        Route::get('/enrollments', [EnrollmentController::class, 'index'])
            ->name('enrollments.index');

        Route::get('/enrollments/create', [EnrollmentController::class, 'create'])
            ->name('enrollments.create');

        Route::get('/enrollments/history', [EnrollmentController::class, 'history'])
            ->name('enrollments.history');

        Route::post('/enrollments/preview', [EnrollmentController::class, 'preview'])
            ->middleware('throttle:20,1')
            ->name('enrollments.preview');
    });

Route::middleware(['auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/dashboard', [StudentPortalController::class, 'dashboard'])
            ->name('dashboard');

        Route::get('/subjects', [StudentPortalController::class, 'subjects'])
            ->name('subjects.index');

        Route::get('/timetable', [StudentPortalController::class, 'timetable'])
            ->name('timetable.index');

        Route::get('/quizzes', [StudentQuizController::class,'index'])
            ->name('quizzes.index');

        Route::post('/quizzes/{quiz}/start', [StudentQuizController::class, 'start'])
            ->whereNumber('quiz')
            ->middleware('throttle:10,1,quiz-start:')
            ->name('quizzes.start');

        Route::get('/quiz-attempts/{attempt}', [StudentQuizController::class, 'show'])
            ->whereNumber('attempt')
            ->name('attempts.show');

        Route::put('/quiz-attempts/{attempt}/answers', [StudentQuizController::class, 'save'])
            ->whereNumber('attempt')
            ->middleware('throttle:120,1,quiz-save:')
            ->name('attempts.save');

        Route::post('/quiz-attempts/{attempt}/submit', [StudentQuizController::class, 'submit'])
            ->whereNumber('attempt')
            ->middleware('throttle:10,1,quiz-submit:')
            ->name('attempts.submit');
    });
