<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\HallController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeatBookingController;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\PublicStudentRegistrationController;
use App\Http\Controllers\StudentRegistrationInviteController;
use App\Http\Controllers\TrialSeatController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/register/{token}', [PublicStudentRegistrationController::class, 'show'])->name('students.register.show');
Route::post('/register/{token}', [PublicStudentRegistrationController::class, 'store'])->name('students.register.store');

Route::get('/setup', [\App\Http\Controllers\SetupController::class, 'show'])->name('setup.show');
Route::post('/setup/test-database', [\App\Http\Controllers\SetupController::class, 'testDatabase'])->name('setup.test-database');
Route::post('/setup/install', [\App\Http\Controllers\SetupController::class, 'install'])->name('setup.install');

Route::get('/install', [\App\Http\Controllers\InstallController::class, 'show'])->name('install.show');
Route::post('/install', [\App\Http\Controllers\InstallController::class, 'run'])->name('install.run');

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'branch', 'page.activity'])->name('dashboard');

Route::middleware(['auth', 'branch', 'page.activity'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/branch', [BranchController::class, 'index'])->name('branch.index');
    Route::patch('/branch', [BranchController::class, 'update'])->name('branch.update');
    Route::post('/active-branch', [BranchController::class, 'switchBranch'])->name('active-branch.switch');

    Route::middleware('platform_admin')->group(function () {
        Route::post('/branch', [BranchController::class, 'store'])->name('branch.store');
        Route::get('/branch/{branch}', [BranchController::class, 'show'])->name('branch.show');
        Route::patch('/branch/{branch}', [BranchController::class, 'updateManaged'])->name('branch.manage.update');
        Route::post('/branch/{branch}/reset-password', [BranchController::class, 'resetPassword'])->name('branch.reset-password');
        Route::delete('/branch/{branch}', [BranchController::class, 'destroy'])->name('branch.destroy');
    });

    Route::get('/halls', [HallController::class, 'index'])->name('halls.index');
    Route::get('/halls/export', [HallController::class, 'export'])->name('halls.export');
    Route::post('/halls', [HallController::class, 'store'])->name('halls.store');
    Route::post('/halls/bulk-delete', [HallController::class, 'bulkDestroy'])->name('halls.bulk-destroy');
    Route::get('/halls/{hall}', [HallController::class, 'show'])->name('halls.show');
    Route::patch('/halls/{hall}', [HallController::class, 'update'])->name('halls.update');
    Route::delete('/halls/{hall}', [HallController::class, 'destroy'])->name('halls.destroy');

    Route::get('/seats', [SeatController::class, 'index'])->name('seats.index');
    Route::get('/seats/data', [SeatController::class, 'data'])->name('seats.data');
    Route::get('/seats/{seat}/schedule', [SeatController::class, 'schedule'])->name('seats.schedule');

    Route::get('/trial-seats', [TrialSeatController::class, 'index'])->name('trial-seats.index');
    Route::get('/trial-seats/data', [TrialSeatController::class, 'data'])->name('trial-seats.data');
    Route::get('/trial-seats/available-seats', [TrialSeatController::class, 'availableSeats'])->name('trial-seats.available-seats');
    Route::post('/trial-seats', [TrialSeatController::class, 'store'])->name('trial-seats.store');

    Route::post('/students/bulk-delete', [StudentController::class, 'bulkDestroy'])->name('students.bulk-destroy');
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    Route::post('/students/registration-invites', [StudentRegistrationInviteController::class, 'store'])->name('students.registration-invites.store');
    Route::get('/students/{student}/photo', [StudentController::class, 'photo'])->name('students.photo');
    Route::get('/students/{student}/id-proof', [StudentController::class, 'idProof'])->name('students.id-proof');
    Route::get('/students/{student}/id-card', [StudentController::class, 'idCard'])->name('students.id-card');
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::patch('/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

    Route::get('/seat-assignments', [SeatBookingController::class, 'index'])->name('seat-assignments.index');
    Route::get('/seat-assignments/available-seats', [SeatBookingController::class, 'availableSeats'])->name('seat-assignments.available-seats');
    Route::post('/seat-assignments', [SeatBookingController::class, 'store'])->name('seat-assignments.store');
    Route::post('/seat-assignments/transfer', [SeatBookingController::class, 'transfer'])->name('seat-assignments.transfer');
    Route::post('/seat-assignments/bulk-cancel', [SeatBookingController::class, 'bulkCancel'])->name('seat-assignments.bulk-cancel');
    Route::get('/seat-assignments/{booking}', [SeatBookingController::class, 'show'])->name('seat-assignments.show');
    Route::post('/seat-assignments/{booking}/cancel', [SeatBookingController::class, 'cancel'])->name('seat-assignments.cancel');
    Route::post('/seat-assignments/{booking}/convert-to-regular', [SeatBookingController::class, 'convertToRegular'])->name('seat-assignments.convert-to-regular');

    Route::get('/enquiries', [EnquiryController::class, 'index'])->name('enquiries.index');
    Route::post('/enquiries', [EnquiryController::class, 'store'])->name('enquiries.store');
    Route::patch('/enquiries/{enquiry}', [EnquiryController::class, 'update'])->name('enquiries.update');
    Route::post('/enquiries/bulk-delete', [EnquiryController::class, 'bulkDestroy'])->name('enquiries.bulk-destroy');
    Route::delete('/enquiries/{enquiry}', [EnquiryController::class, 'destroy'])->name('enquiries.destroy');
    Route::post('/enquiries/{enquiry}/convert', [EnquiryController::class, 'convert'])->name('enquiries.convert');

    Route::get('/fees', [FeeController::class, 'index'])->name('fees.index');
    Route::post('/fees', [FeeController::class, 'store'])->name('fees.store');
    Route::post('/fees/bulk-delete', [FeeController::class, 'bulkDestroy'])->name('fees.bulk-destroy');
    Route::post('/fees/{booking}/pay', [FeeController::class, 'markPaid'])->name('fees.pay');
    Route::post('/fees/{booking}/payments', [FeeController::class, 'recordPayment'])->name('fees.payments.store');
    Route::post('/fees/{booking}/installments/{installment}/pay', [FeeController::class, 'payInstallment'])->name('fees.installments.pay');
    Route::get('/fees/{booking}', [FeeController::class, 'show'])->name('fees.show');
    Route::patch('/fees/{booking}', [FeeController::class, 'update'])->name('fees.update');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/bulk-delete', [NotificationController::class, 'bulkDestroy'])->name('notifications.bulk-destroy');
    Route::get('/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::post('/activity-logs/bulk-delete', [\App\Http\Controllers\ActivityLogController::class, 'bulkDestroy'])->name('activity-logs.bulk-destroy');
    Route::get('/activity-logs/{activityLog}', [\App\Http\Controllers\ActivityLogController::class, 'show'])->name('activity-logs.show');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::patch('/settings/platform', [SettingsController::class, 'updatePlatform'])->name('settings.platform.update')->middleware('platform_admin');
    Route::patch('/settings/platform/plan', [SettingsController::class, 'updatePlatformPlan'])->name('settings.platform.plan.update')->middleware('platform_admin');
    Route::post('/settings/clear-cache', [SettingsController::class, 'clearCache'])->name('settings.clear-cache')->middleware('developer_admin');
});

Route::post('/webhooks/LibControl/seat-map', [WebhookController::class, 'refreshSeatMap'])
    ->name('webhooks.seat-map');

require __DIR__.'/license-server.php';
require __DIR__.'/developer.php';
require __DIR__.'/tenants.php';

require __DIR__.'/auth.php';
