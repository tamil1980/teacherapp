<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\SharedController;
use App\Http\Controllers\Api\TeacherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public endpoints
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');

Route::get('/catalog', [CatalogController::class, 'index']);
Route::get('/teachers', [CatalogController::class, 'teachers']);
Route::get('/teachers/{teacher}', [CatalogController::class, 'teacher']);
Route::get('/teachers/{teacher}/slots', [CatalogController::class, 'slots']);

/*
|--------------------------------------------------------------------------
| Authenticated endpoints
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'update']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Notifications and support (all roles)
    Route::get('/notifications', [SharedController::class, 'notifications']);
    Route::post('/notifications/read-all', [SharedController::class, 'markAllNotificationsRead']);
    Route::post('/notifications/{notification}/read', [SharedController::class, 'markNotificationRead']);

    Route::get('/support', [SharedController::class, 'tickets']);
    Route::post('/support', [SharedController::class, 'storeTicket']);
    Route::get('/support/{ticket}', [SharedController::class, 'ticket']);

    /*
    |----------------------------------------------------------------------
    | Teacher
    |----------------------------------------------------------------------
    */
    Route::prefix('teacher')->middleware('role:teacher')->group(function () {
        Route::get('/dashboard', [TeacherController::class, 'dashboard']);

        Route::get('/profile', [TeacherController::class, 'profile']);
        Route::put('/profile', [TeacherController::class, 'updateProfile']);

        Route::get('/bookings', [TeacherController::class, 'bookings']);
        Route::get('/bookings/{booking}', [TeacherController::class, 'booking']);
        Route::post('/bookings/{booking}/accept', [TeacherController::class, 'accept']);
        Route::post('/bookings/{booking}/reject', [TeacherController::class, 'reject']);
        Route::post('/bookings/{booking}/cancel', [TeacherController::class, 'cancel']);
        Route::post('/bookings/{booking}/reschedule', [TeacherController::class, 'reschedule']);
        Route::post('/bookings/{booking}/complete', [TeacherController::class, 'complete']);
        Route::post('/bookings/{booking}/attendance', [TeacherController::class, 'attendance']);

        Route::get('/availability', [TeacherController::class, 'availability']);
        Route::get('/availability/calendar', [TeacherController::class, 'calendar']);
        Route::post('/availability/rules', [TeacherController::class, 'storeRule']);
        Route::put('/availability/rules/{rule}', [TeacherController::class, 'updateRule']);
        Route::delete('/availability/rules/{rule}', [TeacherController::class, 'destroyRule']);
        Route::post('/availability/slots', [TeacherController::class, 'storeSlot']);
        Route::put('/availability/slots/{slot}', [TeacherController::class, 'updateSlot']);
        Route::delete('/availability/slots/{slot}', [TeacherController::class, 'destroySlot']);

        Route::get('/fees', [TeacherController::class, 'fees']);
        Route::post('/fees', [TeacherController::class, 'storeFee']);
        Route::put('/fees/{fee}', [TeacherController::class, 'updateFee']);
        Route::delete('/fees/{fee}', [TeacherController::class, 'destroyFee']);

        Route::post('/packages', [TeacherController::class, 'storePackage']);
        Route::delete('/packages/{package}', [TeacherController::class, 'destroyPackage']);

        Route::get('/earnings', [TeacherController::class, 'earnings']);
        Route::get('/earnings/{earning}', [TeacherController::class, 'earning']);
        Route::post('/earnings/payout-request', [TeacherController::class, 'requestPayout']);

        Route::get('/locations', [TeacherController::class, 'locations']);
        Route::post('/locations', [TeacherController::class, 'storeLocation']);
        Route::put('/locations/{location}', [TeacherController::class, 'updateLocation']);
        Route::delete('/locations/{location}', [TeacherController::class, 'destroyLocation']);
    });

    /*
    |----------------------------------------------------------------------
    | Parent
    |----------------------------------------------------------------------
    */
    Route::prefix('parent')->middleware('role:parent')->group(function () {
        Route::get('/dashboard', [ParentController::class, 'dashboard']);

        Route::get('/students', [ParentController::class, 'students']);
        Route::post('/students', [ParentController::class, 'storeStudent']);
        Route::get('/students/{student}', [ParentController::class, 'showStudent']);
        Route::put('/students/{student}', [ParentController::class, 'updateStudent']);
        Route::delete('/students/{student}', [ParentController::class, 'destroyStudent']);

        Route::get('/bookings', [ParentController::class, 'bookings']);
        Route::post('/bookings', [ParentController::class, 'storeBooking']);
        Route::get('/bookings/{booking}', [ParentController::class, 'booking']);
        Route::post('/bookings/{booking}/pay', [ParentController::class, 'pay']);
        Route::post('/bookings/{booking}/pay/confirm', [ParentController::class, 'confirmPayment']);
        Route::post('/bookings/{booking}/cancel', [ParentController::class, 'cancel']);
        Route::post('/bookings/{booking}/reschedule', [ParentController::class, 'reschedule']);
        Route::post('/bookings/{booking}/complete', [ParentController::class, 'complete']);
        Route::post('/bookings/{booking}/attendance', [ParentController::class, 'attendance']);
        Route::post('/bookings/{booking}/review', [ParentController::class, 'storeReview']);

        Route::get('/payments', [ParentController::class, 'payments']);
        Route::get('/payments/{payment}', [ParentController::class, 'payment'])->whereNumber('payment');
        Route::get('/payments/{payment}/receipt', [ParentController::class, 'receipt'])->whereNumber('payment');

        Route::get('/reviews', [ParentController::class, 'reviews']);
        Route::put('/reviews/{review}', [ParentController::class, 'updateReview']);
        Route::post('/reviews/{review}/reply', [ParentController::class, 'replyToReview']);
    });

    /*
    |----------------------------------------------------------------------
    | Admin
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        Route::get('/teachers', [AdminController::class, 'teachers']);
        Route::get('/teachers/{teacher}', [AdminController::class, 'teacher']);
        Route::post('/teachers/{teacher}/verify', [AdminController::class, 'verifyTeacher']);
        Route::post('/teachers/{teacher}/reject', [AdminController::class, 'rejectTeacher']);
        Route::post('/teachers/{teacher}/suspend', [AdminController::class, 'suspendTeacher']);
        Route::post('/teachers/{teacher}/reactivate', [AdminController::class, 'reactivateTeacher']);

        Route::get('/parents', [AdminController::class, 'parents']);
        Route::get('/parents/{parent}', [AdminController::class, 'parent']);
        Route::post('/parents/{parent}/suspend', [AdminController::class, 'suspendParent']);

        Route::get('/students', [AdminController::class, 'students']);

        Route::get('/catalog', [AdminController::class, 'catalog']);
        Route::post('/catalog/boards', [AdminController::class, 'storeBoard']);
        Route::put('/catalog/boards/{board}', [AdminController::class, 'updateBoard']);
        Route::delete('/catalog/boards/{board}', [AdminController::class, 'destroyBoard']);
        Route::post('/catalog/subjects', [AdminController::class, 'storeSubject']);
        Route::put('/catalog/subjects/{subject}', [AdminController::class, 'updateSubject']);
        Route::delete('/catalog/subjects/{subject}', [AdminController::class, 'destroySubject']);
        Route::post('/catalog/classes', [AdminController::class, 'storeClass']);
        Route::put('/catalog/classes/{classStandard}', [AdminController::class, 'updateClass']);
        Route::delete('/catalog/classes/{classStandard}', [AdminController::class, 'destroyClass']);

        Route::get('/bookings', [AdminController::class, 'bookings']);
        Route::get('/bookings/{booking}', [AdminController::class, 'booking']);
        Route::post('/bookings/{booking}/status', [AdminController::class, 'updateBookingStatus']);

        Route::get('/payments', [AdminController::class, 'payments']);
        Route::get('/payments/{payment}', [AdminController::class, 'payment'])->whereNumber('payment');
        Route::post('/payments/{payment}/refund', [AdminController::class, 'refundPayment'])->whereNumber('payment');
        Route::get('/refunds', [AdminController::class, 'refunds']);

        Route::get('/payouts', [AdminController::class, 'payouts']);
        Route::post('/payouts', [AdminController::class, 'storePayout']);
        Route::get('/payouts/{payout}', [AdminController::class, 'payout']);
        Route::post('/payouts/{payout}/complete', [AdminController::class, 'completePayout']);

        Route::get('/reviews', [AdminController::class, 'reviews']);
        Route::post('/reviews/{review}/moderate', [AdminController::class, 'moderateReview']);

        Route::get('/disputes', [AdminController::class, 'disputes']);
        Route::post('/disputes/{dispute}/resolve', [AdminController::class, 'resolveDispute']);

        Route::get('/offers', [AdminController::class, 'offers']);
        Route::post('/offers', [AdminController::class, 'storeOffer']);
        Route::put('/offers/{offer}', [AdminController::class, 'updateOffer']);
        Route::delete('/offers/{offer}', [AdminController::class, 'destroyOffer']);

        Route::get('/settings', [AdminController::class, 'settings']);
        Route::put('/settings', [AdminController::class, 'updateSettings']);

        Route::get('/audit-logs', [AdminController::class, 'auditLogs']);
    });
});
