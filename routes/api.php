<?php

use App\Http\Controllers\Api\Mobile\AggregateController;
use App\Http\Controllers\Api\Mobile\AttendanceController;
use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\BookReservationController;
use App\Http\Controllers\Api\Mobile\BorrowRequestController;
use App\Http\Controllers\Api\Mobile\BorrowingController;
use App\Http\Controllers\Api\Mobile\CatalogController;
use App\Http\Controllers\Api\Mobile\FacultyClassroomController;
use App\Http\Controllers\Api\Mobile\FacultyFolderController;
use App\Http\Controllers\Api\Mobile\FacultyRegistrationController;
use App\Http\Controllers\Api\Mobile\FeedbackController;
use App\Http\Controllers\Api\Mobile\IdCardController;
use App\Http\Controllers\Api\Mobile\NotificationController;
use App\Http\Controllers\Api\Mobile\ProfileController;
use App\Http\Controllers\Api\Mobile\RoomReservationController;
use App\Http\Controllers\Api\Mobile\StudentClassroomController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->name('api.mobile.')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'message' => 'PANTAS mobile API is running.',
            'data' => [
                'service' => 'pantas-mobile-api',
                'status' => 'ok',
            ],
        ]);
    })->name('health');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login');

    Route::post('/register/faculty', [FacultyRegistrationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('register.faculty');

    Route::prefix('catalog')->name('catalog.')->group(function () {
        Route::get('/search', [CatalogController::class, 'search'])->name('search');
        Route::get('/filters', [CatalogController::class, 'filters'])->name('filters');
        Route::get('/new-arrivals', [CatalogController::class, 'newArrivals'])->name('new-arrivals');
        Route::get('/books/{book}', [CatalogController::class, 'book'])->name('books.show');
        Route::get('/ebooks/{ebook}', [CatalogController::class, 'ebook'])->name('ebooks.show');
    });

    // Student-facing change-password — requires password-change scoped token
    Route::post('/student/change-password', [AuthController::class, 'studentChangePassword'])
        ->middleware(['auth:sanctum', 'sanctum.ability:password-change'])
        ->name('student.change-password');

    Route::middleware('auth:sanctum')->group(function () {
        // All routes in this group require full-access ability
        Route::middleware('sanctum.ability:full-access')->group(function () {
            Route::get('/home', [AggregateController::class, 'home'])->name('home');
            Route::get('/home/recommendations', [AggregateController::class, 'recommendations'])->name('home.recommendations');
            Route::get('/home/faculty-recommendations', [StudentClassroomController::class, 'facultyRecommendations'])->name('home.faculty-recommendations');
            Route::get('/borrow-overview', [AggregateController::class, 'borrowOverview'])->name('borrow-overview');
            Route::get('/rooms/dashboard', [AggregateController::class, 'roomsDashboard'])->name('rooms.dashboard');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('/change-password', [AuthController::class, 'changePassword'])->name('change-password');
            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::get('/profile', [AuthController::class, 'me'])->name('profile');
            Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
            Route::post('/profile/update-picture', [ProfileController::class, 'updatePicture'])->name('profile.update-picture');
            Route::get('/borrowed-books', [BorrowingController::class, 'active'])->name('borrowed-books');
            Route::get('/borrow-history', [BorrowingController::class, 'history'])->name('borrow-history');
            Route::get('/borrow-limits', [BorrowingController::class, 'limits'])->name('borrow-limits');
            Route::post('/borrow-cart/submit', [BorrowingController::class, 'submitCart'])->name('borrow-cart.submit');
            Route::get('/borrow-requests', [BorrowRequestController::class, 'index'])->name('borrow-requests.index');
            Route::get('/borrow-requests/{borrowRequest}', [BorrowRequestController::class, 'show'])->name('borrow-requests.show');
            Route::delete('/borrow-requests/{borrowRequest}', [BorrowRequestController::class, 'destroy'])->name('borrow-requests.destroy');
            Route::get('/books/reservations', [BookReservationController::class, 'index'])->name('books.reservations.index');
            Route::post('/books/reservations', [BookReservationController::class, 'store'])->name('books.reservations.store');
            Route::get('/books/reservations/{reservation}', [BookReservationController::class, 'show'])->name('books.reservations.show');
            Route::delete('/books/reservations/{reservation}', [BookReservationController::class, 'destroy'])->name('books.reservations.destroy');
            Route::get('/rooms', [RoomReservationController::class, 'rooms'])->name('rooms.index');
            Route::get('/rooms/availability', [RoomReservationController::class, 'availability'])->name('rooms.availability');
            Route::get('/rooms/reservations', [RoomReservationController::class, 'index'])->name('rooms.reservations.index');
            Route::post('/rooms/reservations', [RoomReservationController::class, 'store'])->name('rooms.reservations.store');
            Route::get('/rooms/reservations/{reservation}', [RoomReservationController::class, 'show'])->name('rooms.reservations.show');
            Route::delete('/rooms/reservations/{reservation}', [RoomReservationController::class, 'destroy'])->name('rooms.reservations.destroy');
            Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
            Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
            Route::get('/attendance/preview', [AttendanceController::class, 'preview'])->name('attendance.preview');
            Route::get('/id-card', [IdCardController::class, 'show'])->name('id-card.show');

            Route::get('/classrooms', [StudentClassroomController::class, 'index'])->name('classrooms.index');
            Route::post('/classrooms/join', [StudentClassroomController::class, 'join'])->name('classrooms.join');
            Route::get('/classrooms/{id}', [StudentClassroomController::class, 'show'])->name('classrooms.show');
            Route::delete('/classrooms/{id}/leave', [StudentClassroomController::class, 'leave'])->name('classrooms.leave');

            Route::get('/faculty/classrooms', [FacultyClassroomController::class, 'index'])->name('faculty.classrooms.index');
            Route::post('/faculty/classrooms', [FacultyClassroomController::class, 'store'])->name('faculty.classrooms.store');
            Route::get('/faculty/classrooms/{id}', [FacultyClassroomController::class, 'show'])->name('faculty.classrooms.show');
            Route::patch('/faculty/classrooms/{id}', [FacultyClassroomController::class, 'update'])->name('faculty.classrooms.update');
            Route::delete('/faculty/classrooms/{id}', [FacultyClassroomController::class, 'destroy'])->name('faculty.classrooms.destroy');
            Route::get('/faculty/classrooms/{id}/members', [FacultyClassroomController::class, 'members'])->name('faculty.classrooms.members');
            Route::post('/faculty/classrooms/{id}/members/{member}/approve', [FacultyClassroomController::class, 'approveMember'])->name('faculty.classrooms.members.approve');
            Route::post('/faculty/classrooms/{id}/members/{member}/reject', [FacultyClassroomController::class, 'rejectMember'])->name('faculty.classrooms.members.reject');
            Route::post('/faculty/classrooms/{id}/regenerate-code', [FacultyClassroomController::class, 'regenerateCode'])->name('faculty.classrooms.regenerate-code');
            Route::post('/faculty/classrooms/{id}/folders', [FacultyClassroomController::class, 'shareFolder'])->name('faculty.classrooms.folders.share');
            Route::delete('/faculty/classrooms/{id}/folders/{folderId}', [FacultyClassroomController::class, 'unshareFolder'])->name('faculty.classrooms.folders.unshare');

            Route::get('/faculty/folders', [FacultyFolderController::class, 'index'])->name('faculty.folders.index');
            Route::post('/faculty/folders', [FacultyFolderController::class, 'store'])->name('faculty.folders.store');
            Route::get('/faculty/folders/{id}', [FacultyFolderController::class, 'show'])->name('faculty.folders.show');
            Route::patch('/faculty/folders/{id}', [FacultyFolderController::class, 'update'])->name('faculty.folders.update');
            Route::delete('/faculty/folders/{id}', [FacultyFolderController::class, 'destroy'])->name('faculty.folders.destroy');
            Route::post('/faculty/folders/{id}/books', [FacultyFolderController::class, 'addBooks'])->name('faculty.folders.books.add');
            Route::delete('/faculty/folders/{id}/books/{bookId}', [FacultyFolderController::class, 'removeBook'])->name('faculty.folders.books.remove');
        });

        // Staff-initiated student password reset
        Route::post('/students/{student}/reset-password', [AuthController::class, 'staffResetPassword'])
            ->name('students.reset-password');
    });
});
