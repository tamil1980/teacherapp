<?php

use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminCatalogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDisputeController;
use App\Http\Controllers\Admin\AdminOfferController;
use App\Http\Controllers\Admin\AdminParentController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminPayoutController;
use App\Http\Controllers\Admin\AdminRefundController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminTeacherController;
use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\BookingWebController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Parent\ParentBookingController;
use App\Http\Controllers\Parent\ParentDashboardController;
use App\Http\Controllers\Parent\ParentPaymentController;
use App\Http\Controllers\Parent\ParentReviewController;
use App\Http\Controllers\Parent\ParentStudentController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\TeacherDirectoryController;
use App\Http\Controllers\Teacher\TeacherAvailabilityController;
use App\Http\Controllers\Teacher\TeacherBookingController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Teacher\TeacherEarningsController;
use App\Http\Controllers\Teacher\TeacherFeeController;
use App\Http\Controllers\Teacher\TeacherLocationController;
use App\Http\Controllers\Teacher\TeacherProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/teachers', [TeacherDirectoryController::class, 'index'])->name('teachers.index');
Route::get('/teachers/{teacher}', [TeacherDirectoryController::class, 'show'])->name('teachers.show');

Route::get('/subjects', [CatalogController::class, 'subjects'])->name('subjects.index');
Route::get('/subjects/{subject}', [CatalogController::class, 'subject'])->name('subjects.show');
Route::get('/boards', [CatalogController::class, 'boards'])->name('boards.index');
Route::get('/boards/{board}', [CatalogController::class, 'board'])->name('boards.show');
Route::get('/classes', [CatalogController::class, 'classes'])->name('classes.index');
Route::get('/classes/{classStandard}', [CatalogController::class, 'class'])->name('classes.show');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/cancellation-policy', [PageController::class, 'cancellationPolicy'])->name('cancellation-policy');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login'])->name('login.attempt');
    Route::get('/register/parent', [WebAuthController::class, 'showRegister'])->name('register.parent');
    Route::get('/register/teacher', [WebAuthController::class, 'showRegister'])->name('register.teacher');
    Route::post('/register', [WebAuthController::class, 'register'])->name('register');
});

Route::post('/logout', [WebAuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Parent / student area
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:parent'])->group(function () {
    Route::get('/parent/dashboard', [ParentDashboardController::class, 'index'])->name('parent.dashboard');

    Route::get('/parent/students', [ParentStudentController::class, 'index'])->name('parent.students.index');
    Route::get('/parent/students/create', [ParentStudentController::class, 'create'])->name('parent.students.create');
    Route::post('/parent/students', [ParentStudentController::class, 'store'])->name('parent.students.store');
    Route::get('/parent/students/{student}', [ParentStudentController::class, 'show'])->name('parent.students.show');
    Route::get('/parent/students/{student}/edit', [ParentStudentController::class, 'edit'])->name('parent.students.edit');
    Route::put('/parent/students/{student}', [ParentStudentController::class, 'update'])->name('parent.students.update');
    Route::delete('/parent/students/{student}', [ParentStudentController::class, 'destroy'])->name('parent.students.destroy');

    Route::get('/parent/bookings', [ParentBookingController::class, 'index'])->name('parent.bookings.index');
    Route::get('/parent/bookings/{booking}', [ParentBookingController::class, 'show'])->name('parent.bookings.show');
    Route::post('/parent/bookings/{booking}/cancel', [ParentBookingController::class, 'cancel'])->name('parent.bookings.cancel');
    Route::post('/parent/bookings/{booking}/reschedule', [ParentBookingController::class, 'reschedule'])->name('parent.bookings.reschedule');
    Route::post('/parent/bookings/{booking}/complete', [ParentBookingController::class, 'complete'])->name('parent.bookings.complete');
    Route::post('/parent/bookings/{booking}/attendance', [ParentBookingController::class, 'attendance'])->name('parent.bookings.attendance');

    Route::get('/parent/payments', [ParentPaymentController::class, 'index'])->name('parent.payments.index');
    Route::get('/parent/payments/{payment}', [ParentPaymentController::class, 'show'])->name('parent.payments.show');
    Route::get('/parent/payments/{payment}/receipt', [ParentPaymentController::class, 'receipt'])->name('parent.payments.receipt');

    Route::post('/parent/bookings/{booking}/review', [ParentReviewController::class, 'store'])->name('parent.reviews.store');
    Route::get('/parent/reviews', [ParentReviewController::class, 'index'])->name('parent.reviews.index');
    Route::post('/parent/reviews/{review}', [ParentReviewController::class, 'update'])->name('parent.reviews.update');
    Route::post('/parent/reviews/{review}/reply', [ParentReviewController::class, 'reply'])->name('parent.reviews.reply');
});

/*
|--------------------------------------------------------------------------
| Booking & payment (checkout)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/teachers/{teacher}/book', [BookingWebController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [BookingWebController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}/checkout', [BookingWebController::class, 'checkout'])->name('bookings.checkout');
    Route::get('/bookings/{booking}/pay', [BookingWebController::class, 'pay'])->name('bookings.pay');
    Route::post('/bookings/{booking}/pay', [BookingWebController::class, 'processPayment'])->name('bookings.pay.process');
    Route::get('/bookings/{booking}/result', [BookingWebController::class, 'result'])->name('bookings.result');
    Route::post('/bookings/{booking}/hold/release', [BookingWebController::class, 'releaseHold'])->name('bookings.hold.release');
    Route::get('/bookings/{booking}/meeting', [BookingWebController::class, 'meeting'])->name('bookings.meeting');
    Route::post('/bookings/{booking}/meeting', [BookingWebController::class, 'storeMeetingLink'])->name('bookings.meeting.store');
});

/*
|--------------------------------------------------------------------------
| Teacher area
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('teacher.dashboard');

    Route::get('/profile', [TeacherProfileController::class, 'edit'])->name('teacher.profile.edit');
    Route::put('/profile', [TeacherProfileController::class, 'update'])->name('teacher.profile.update');
    Route::put('/profile/subjects', [TeacherProfileController::class, 'updateSubjects'])->name('teacher.profile.subjects');
    Route::put('/profile/boards', [TeacherProfileController::class, 'updateBoards'])->name('teacher.profile.boards');
    Route::put('/profile/classes', [TeacherProfileController::class, 'updateClasses'])->name('teacher.profile.classes');
    Route::put('/profile/modes', [TeacherProfileController::class, 'updateModes'])->name('teacher.profile.modes');
    Route::post('/profile/photo', [TeacherProfileController::class, 'updatePhoto'])->name('teacher.profile.photo');
    Route::post('/profile/qualifications', [TeacherProfileController::class, 'storeQualification'])->name('teacher.profile.qualifications.store');
    Route::delete('/profile/qualifications/{qualification}', [TeacherProfileController::class, 'destroyQualification'])->name('teacher.profile.qualifications.destroy');
    Route::post('/profile/documents', [TeacherProfileController::class, 'storeDocument'])->name('teacher.profile.documents.store');
    Route::delete('/profile/documents/{document}', [TeacherProfileController::class, 'destroyDocument'])->name('teacher.profile.documents.destroy');

    Route::get('/locations', [TeacherLocationController::class, 'index'])->name('teacher.locations.index');
    Route::post('/locations', [TeacherLocationController::class, 'store'])->name('teacher.locations.store');
    Route::put('/locations/{location}', [TeacherLocationController::class, 'update'])->name('teacher.locations.update');
    Route::delete('/locations/{location}', [TeacherLocationController::class, 'destroy'])->name('teacher.locations.destroy');

    Route::get('/availability', [TeacherAvailabilityController::class, 'index'])->name('teacher.availability.index');
    Route::put('/availability/rules', [TeacherAvailabilityController::class, 'storeRule'])->name('teacher.availability.rules.store');
    Route::put('/availability/rules/{rule}', [TeacherAvailabilityController::class, 'updateRule'])->name('teacher.availability.rules.update');
    Route::delete('/availability/rules/{rule}', [TeacherAvailabilityController::class, 'destroyRule'])->name('teacher.availability.rules.destroy');
    Route::post('/availability/slots', [TeacherAvailabilityController::class, 'storeSlot'])->name('teacher.availability.slots.store');
    Route::put('/availability/slots/{slot}', [TeacherAvailabilityController::class, 'updateSlot'])->name('teacher.availability.slots.update');
    Route::delete('/availability/slots/{slot}', [TeacherAvailabilityController::class, 'destroySlot'])->name('teacher.availability.slots.destroy');
    Route::get('/availability/calendar', [TeacherAvailabilityController::class, 'calendar'])->name('teacher.availability.calendar');

    Route::get('/fees', [TeacherFeeController::class, 'index'])->name('teacher.fees.index');
    Route::post('/fees', [TeacherFeeController::class, 'store'])->name('teacher.fees.store');
    Route::put('/fees/{fee}', [TeacherFeeController::class, 'update'])->name('teacher.fees.update');
    Route::delete('/fees/{fee}', [TeacherFeeController::class, 'destroy'])->name('teacher.fees.destroy');
    Route::get('/packages', [TeacherFeeController::class, 'packages'])->name('teacher.packages.index');
    Route::post('/packages', [TeacherFeeController::class, 'storePackage'])->name('teacher.packages.store');
    Route::delete('/packages/{package}', [TeacherFeeController::class, 'destroyPackage'])->name('teacher.packages.destroy');

    Route::get('/bookings', [TeacherBookingController::class, 'index'])->name('teacher.bookings.index');
    Route::get('/bookings/{booking}', [TeacherBookingController::class, 'show'])->name('teacher.bookings.show');
    Route::post('/bookings/{booking}/accept', [TeacherBookingController::class, 'accept'])->name('teacher.bookings.accept');
    Route::post('/bookings/{booking}/reject', [TeacherBookingController::class, 'reject'])->name('teacher.bookings.reject');
    Route::post('/bookings/{booking}/cancel', [TeacherBookingController::class, 'cancel'])->name('teacher.bookings.cancel');
    Route::post('/bookings/{booking}/reschedule', [TeacherBookingController::class, 'reschedule'])->name('teacher.bookings.reschedule');
    Route::post('/bookings/{booking}/complete', [TeacherBookingController::class, 'complete'])->name('teacher.bookings.complete');
    Route::post('/bookings/{booking}/attendance', [TeacherBookingController::class, 'attendance'])->name('teacher.bookings.attendance');

    Route::get('/earnings', [TeacherEarningsController::class, 'index'])->name('teacher.earnings.index');
    Route::get('/earnings/{earning}', [TeacherEarningsController::class, 'show'])->name('teacher.earnings.show');
    Route::post('/earnings/payout-request', [TeacherEarningsController::class, 'requestPayout'])->name('teacher.earnings.payout.request');
});

/*
|--------------------------------------------------------------------------
| Shared authenticated pages
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
    Route::get('/support/create', [SupportController::class, 'create'])->name('support.create');
    Route::post('/support', [SupportController::class, 'store'])->name('support.store');
    Route::get('/support/{ticket}', [SupportController::class, 'show'])->name('support.show');
});

/*
|--------------------------------------------------------------------------
| Admin area
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/teachers', [AdminTeacherController::class, 'index'])->name('admin.teachers.index');
    Route::get('/teachers/{teacher}', [AdminTeacherController::class, 'show'])->name('admin.teachers.show');
    Route::post('/teachers/{teacher}/verify', [AdminTeacherController::class, 'verify'])->name('admin.teachers.verify');
    Route::post('/teachers/{teacher}/reject', [AdminTeacherController::class, 'reject'])->name('admin.teachers.reject');
    Route::post('/teachers/{teacher}/suspend', [AdminTeacherController::class, 'suspend'])->name('admin.teachers.suspend');
    Route::post('/teachers/{teacher}/reactivate', [AdminTeacherController::class, 'reactivate'])->name('admin.teachers.reactivate');
    Route::post('/documents/{document}/review', [AdminTeacherController::class, 'reviewDocument'])->name('admin.documents.review');

    Route::get('/parents', [AdminParentController::class, 'index'])->name('admin.parents.index');
    Route::get('/parents/{parent}', [AdminParentController::class, 'show'])->name('admin.parents.show');
    Route::post('/parents/{parent}/suspend', [AdminParentController::class, 'suspend'])->name('admin.parents.suspend');

    Route::get('/students', [AdminParentController::class, 'students'])->name('admin.students.index');

    Route::get('/catalog', [AdminCatalogController::class, 'index'])->name('admin.catalog.index');
    Route::post('/catalog/boards', [AdminCatalogController::class, 'storeBoard'])->name('admin.catalog.boards.store');
    Route::put('/catalog/boards/{board}', [AdminCatalogController::class, 'updateBoard'])->name('admin.catalog.boards.update');
    Route::delete('/catalog/boards/{board}', [AdminCatalogController::class, 'destroyBoard'])->name('admin.catalog.boards.destroy');
    Route::post('/catalog/subjects', [AdminCatalogController::class, 'storeSubject'])->name('admin.catalog.subjects.store');
    Route::put('/catalog/subjects/{subject}', [AdminCatalogController::class, 'updateSubject'])->name('admin.catalog.subjects.update');
    Route::delete('/catalog/subjects/{subject}', [AdminCatalogController::class, 'destroySubject'])->name('admin.catalog.subjects.destroy');
    Route::post('/catalog/classes', [AdminCatalogController::class, 'storeClass'])->name('admin.catalog.classes.store');
    Route::put('/catalog/classes/{classStandard}', [AdminCatalogController::class, 'updateClass'])->name('admin.catalog.classes.update');
    Route::delete('/catalog/classes/{classStandard}', [AdminCatalogController::class, 'destroyClass'])->name('admin.catalog.classes.destroy');

    Route::get('/bookings', [AdminBookingController::class, 'index'])->name('admin.bookings.index');
    Route::get('/bookings/{booking}', [AdminBookingController::class, 'show'])->name('admin.bookings.show');
    Route::post('/bookings/{booking}/status', [AdminBookingController::class, 'updateStatus'])->name('admin.bookings.status');
    Route::post('/bookings/{booking}/complete', [AdminBookingController::class, 'complete'])->name('admin.bookings.complete');

    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('admin.payments.index');
    Route::get('/payments/{payment}', [AdminPaymentController::class, 'show'])->name('admin.payments.show');
    Route::post('/payments/{payment}/refund', [AdminRefundController::class, 'store'])->name('admin.payments.refund');
    Route::get('/refunds', [AdminRefundController::class, 'index'])->name('admin.refunds.index');

    Route::get('/payouts', [AdminPayoutController::class, 'index'])->name('admin.payouts.index');
    Route::get('/payouts/{payout}', [AdminPayoutController::class, 'show'])->name('admin.payouts.show');
    Route::post('/payouts', [AdminPayoutController::class, 'store'])->name('admin.payouts.store');
    Route::post('/payouts/{payout}/complete', [AdminPayoutController::class, 'complete'])->name('admin.payouts.complete');

    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('admin.reviews.index');
    Route::post('/reviews/{review}/moderate', [AdminReviewController::class, 'moderate'])->name('admin.reviews.moderate');

    Route::get('/disputes', [AdminDisputeController::class, 'index'])->name('admin.disputes.index');
    Route::post('/disputes/{dispute}/resolve', [AdminDisputeController::class, 'resolve'])->name('admin.disputes.resolve');

    Route::get('/offers', [AdminOfferController::class, 'index'])->name('admin.offers.index');
    Route::post('/offers', [AdminOfferController::class, 'store'])->name('admin.offers.store');
    Route::put('/offers/{offer}', [AdminOfferController::class, 'update'])->name('admin.offers.update');
    Route::delete('/offers/{offer}', [AdminOfferController::class, 'destroy'])->name('admin.offers.destroy');

    Route::get('/settings', [AdminSettingController::class, 'index'])->name('admin.settings.index');
    Route::put('/settings', [AdminSettingController::class, 'update'])->name('admin.settings.update');
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('admin.audit-logs.index');
});
