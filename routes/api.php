<?php

use App\Http\Controllers\Api\Mobile\AggregateController;
use App\Http\Controllers\Api\Mobile\AttendanceController;
use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\BorrowingController;
use App\Http\Controllers\Api\Mobile\CatalogController;
use App\Http\Controllers\Api\Mobile\FeedbackController;
use App\Http\Controllers\Api\Mobile\NotificationController;
use App\Http\Controllers\Api\Mobile\RoomReservationController;
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
            Route::get('/borrow-overview', [AggregateController::class, 'borrowOverview'])->name('borrow-overview');
            Route::get('/rooms/dashboard', [AggregateController::class, 'roomsDashboard'])->name('rooms.dashboard');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('/change-password', [AuthController::class, 'changePassword'])->name('change-password');
            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::get('/profile', [AuthController::class, 'me'])->name('profile');
            Route::get('/borrowed-books', [BorrowingController::class, 'active'])->name('borrowed-books');
            Route::get('/borrow-history', [BorrowingController::class, 'history'])->name('borrow-history');
            Route::get('/borrow-limits', [BorrowingController::class, 'limits'])->name('borrow-limits');
            Route::post('/borrow-cart/submit', [BorrowingController::class, 'submitCart'])->name('borrow-cart.submit');
            Route::get('/rooms', [RoomReservationController::class, 'rooms'])->name('rooms.index');
            Route::get('/rooms/availability', [RoomReservationController::class, 'availability'])->name('rooms.availability');
            Route::get('/rooms/reservations', [RoomReservationController::class, 'index'])->name('rooms.reservations.index');
            Route::post('/rooms/reservations', [RoomReservationController::class, 'store'])->name('rooms.reservations.store');
            Route::get('/rooms/reservations/{reservation}', [RoomReservationController::class, 'show'])->name('rooms.reservations.show');
            Route::delete('/rooms/reservations/{reservation}', [RoomReservationController::class, 'destroy'])->name('rooms.reservations.destroy');
            Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
            Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
            Route::get('/attendance/preview', [AttendanceController::class, 'preview'])->name('attendance.preview');
        });

        // Staff-initiated student password reset
        Route::post('/students/{student}/reset-password', [AuthController::class, 'staffResetPassword'])
            ->name('students.reset-password');
    });
});
