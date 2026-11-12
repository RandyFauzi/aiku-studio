<?php

use App\Http\Controllers\AdminNoteController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContributorController;
use App\Http\Controllers\DailyWorkReportController;
use App\Http\Controllers\DeviceAccountController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\MyRecordingController;
use App\Http\Controllers\RecordingController;
use App\Http\Controllers\RecordingMatchingController;
use App\Http\Controllers\RecordStreakController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ShiftDayController;
use App\Http\Controllers\SyncController;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureAdministrator;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::view('/login.html', 'auth.login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::middleware(['auth', EnsureActiveAccount::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/', fn () => auth()->user()->role === 'contributor' ? redirect('/me') : view('panel.index'));

    // Contributor Portal (Separate Blade Pages for Each Function)
    Route::get('/me', fn () => view('portal.home'))->name('portal.home');
    Route::get('/me/laporan', fn () => view('portal.work-reports'))->name('portal.work-reports');
    Route::get('/me/jadwal', fn () => view('portal.schedule'))->name('portal.schedule');
    Route::get('/me/rekaman', fn () => view('portal.recordings'))->name('portal.recordings');
    Route::get('/me/slip', fn () => view('portal.payslips'))->name('portal.payslips');
    Route::get('/me/profil', fn () => view('portal.profile'))->name('portal.profile');
    Route::get('/me/profile', fn () => redirect('/me/profil'));
    Route::get('/api/me/attendance', [AttendanceController::class, 'mine']);
    Route::get('/api/me/streak', [RecordStreakController::class, 'mine'])->middleware('throttle:60,1');
    Route::post('/api/me/streak/celebrate', [RecordStreakController::class, 'celebrate'])->middleware('throttle:30,1');
    Route::post('/api/me/attendance', [AttendanceController::class, 'checkIn'])->middleware('throttle:20,1');
    Route::post('/api/me/attendance/qr', [AttendanceController::class, 'checkInQr'])->middleware('throttle:20,1');
    Route::get('/api/me/work-reports', [DailyWorkReportController::class, 'mine']);
    Route::get('/api/report-tasks', [DailyWorkReportController::class, 'taskOptions'])->middleware('throttle:120,1');
    Route::put('/api/me/work-report', [DailyWorkReportController::class, 'save'])->middleware('throttle:30,1');
    Route::post('/api/me/work-report', [DailyWorkReportController::class, 'save'])->middleware('throttle:30,1');
    Route::delete('/api/me/work-reports/{report}', [DailyWorkReportController::class, 'destroy'])->middleware('throttle:30,1');
    Route::get('/api/me/recordings', [MyRecordingController::class, 'index']);
    Route::get('/api/me/payslips', [PayslipController::class, 'mine']);
    Route::put('/api/me/password', [AuthController::class, 'updatePassword'])->middleware('throttle:10,1');

    // Workers may only open their final archived slips; live calculations are admin-only.
    Route::get('/slip/live/{worker}', [PayslipController::class, 'live'])->middleware(EnsureAdministrator::class);
    Route::get('/slip/{payslip}', [PayslipController::class, 'page'])->whereNumber('payslip');
    Route::get('/{type}/{path}', [PayslipController::class, 'file'])->where('type', 'evidence_images|pdf_slips')->where('path', '.+');
    Route::get('/api/payslips/{payslip}/export', [PayslipController::class, 'export'])->whereNumber('payslip');
    Route::get('/api/payroll/export-live/{worker}', [PayslipController::class, 'exportLive'])->middleware(EnsureAdministrator::class);

    Route::middleware(EnsureAdministrator::class)->group(function () {
        Route::put('/api/report-tasks/label', [DailyWorkReportController::class, 'saveTaskLabel'])->middleware('throttle:60,1');
        Route::get('/attendance/barcode/poster', [AttendanceController::class, 'barcodePoster'])->name('attendance.barcode.poster');
        Route::get('/api/attendance/wifi-config', [AttendanceController::class, 'getWifiConfig']);
        Route::post('/api/attendance/wifi-config', [AttendanceController::class, 'saveWifiConfig']);
        Route::view('/attendance', 'panel.attendance')->name('attendance');
        Route::view('/attendance.html', 'panel.attendance');
        Route::get('/api/attendance', [AttendanceController::class, 'index']);
        Route::post('/api/attendance/manual', [AttendanceController::class, 'manualCheckIn']);
        Route::delete('/api/attendance/{attendance}', [AttendanceController::class, 'destroy'])->whereNumber('attendance');
        Route::view('/work-reports', 'panel.work-reports')->name('work-reports');
        Route::view('/work-reports.html', 'panel.work-reports');
        Route::get('/api/work-reports', [DailyWorkReportController::class, 'index']);
        Route::post('/api/work-reports', [DailyWorkReportController::class, 'adminStore']);
        Route::post('/api/work-reports/{report}/streak-verify', [RecordStreakController::class, 'verify'])->whereNumber('report');
        Route::put('/api/work-reports/{report}', [DailyWorkReportController::class, 'adminUpdate']);
        Route::delete('/api/work-reports/{report}', [DailyWorkReportController::class, 'adminDestroy']);
        Route::get('/api/work-reports/assist-matches', [DailyWorkReportController::class, 'assistMatches']);
        Route::get('/api/recording-matching', [RecordingMatchingController::class, 'index']);
        Route::get('/api/studio-recordings/{recordId}/recommendations', [RecordingMatchingController::class, 'recommendations'])->middleware('throttle:120,1');
        Route::post('/api/recording-matching/confirm', [RecordingMatchingController::class, 'confirm'])->middleware('throttle:30,1');
        Route::post('/api/work-reports/assist-assign', [DailyWorkReportController::class, 'assistAssign']);
        Route::post('/api/work-reports/assist-unassign', [DailyWorkReportController::class, 'assistUnassign']);
        Route::view('/devices', 'panel.devices')->name('devices');
        Route::view('/devices.html', 'panel.devices');
        foreach (['index', 'contributors', 'create', 'notes', 'payslips', 'profit', 'recordings', 'shifts'] as $page) {
            $view = 'panel.'.$page;
            Route::view('/'.$page, $view)->name($page);
            Route::view('/'.$page.'.html', $view);
        }

        Route::get('/api/admin-notes', [AdminNoteController::class, 'index']);
        Route::post('/api/admin-notes', [AdminNoteController::class, 'store']);
        Route::put('/api/admin-notes/{adminNote}', [AdminNoteController::class, 'update'])->whereNumber('adminNote');
        Route::patch('/api/admin-notes/{adminNote}/toggle', [AdminNoteController::class, 'toggleComplete'])->whereNumber('adminNote');
        Route::patch('/api/admin-notes/{adminNote}/pin', [AdminNoteController::class, 'togglePin'])->whereNumber('adminNote');
        Route::delete('/api/admin-notes/{adminNote}', [AdminNoteController::class, 'destroy'])->whereNumber('adminNote');

        // Keep session authentication and CSRF on legacy-compatible JSON endpoints.
        Route::prefix('api')->group(function () {
            Route::prefix('devices')->group(function () {
                Route::get('/', [DeviceAccountController::class, 'index']);
                Route::post('/accounts', [DeviceAccountController::class, 'store']);
                Route::match(['put', 'patch'], '/accounts/{deviceAccount}', [DeviceAccountController::class, 'update']);
                Route::patch('/accounts/{deviceAccount}/activate', [DeviceAccountController::class, 'activate']);
                Route::delete('/accounts/{deviceAccount}', [DeviceAccountController::class, 'destroy']);
            });
            Route::get('/slips', [PayslipController::class, 'index']);
            Route::get('/contributors/studio-summary', [ContributorController::class, 'studioSummary']);
            Route::get('/contributors', [ContributorController::class, 'index']);
            Route::post('/contributors', [ContributorController::class, 'store']);
            Route::get('/contributors/{contributor}/performance', [ContributorController::class, 'performance'])->whereNumber('contributor');
            Route::put('/contributors/{contributor}', [ContributorController::class, 'update']);
            Route::patch('/contributors/{contributor}/role', [ContributorController::class, 'updateRole']);
            Route::delete('/contributors/{contributor}', [ContributorController::class, 'destroy']);
            Route::post('/upload-evidence', [PayslipController::class, 'upload']);
            Route::post('/payslips', [PayslipController::class, 'store']);
            Route::get('/payslips/{payslip}', [PayslipController::class, 'show'])->whereNumber('payslip');
            Route::put('/payslips/{payslip}', [PayslipController::class, 'update']);
            Route::delete('/payslips/{payslip}', [PayslipController::class, 'destroy']);
            Route::patch('/payslips/{payslip}/status', [PayslipController::class, 'status']);
            Route::post('/payslips/{payslip}/email', [PayslipController::class, 'email'])->middleware('throttle:10,1');
            Route::get('/studio-recordings', [RecordingController::class, 'index']);
            Route::put('/studio-recordings/batch-assign', [RecordingController::class, 'assign']);
            Route::get('/studio-accounts', [SettingController::class, 'accounts']);
            Route::post('/studio-accounts', [SettingController::class, 'addAccounts']);
            Route::delete('/studio-accounts/{email}', [SettingController::class, 'deleteAccount']);
            Route::put('/studio-accounts/{email}/toggle', [SettingController::class, 'toggleAccount']);
            Route::get('/shifts/config', [SettingController::class, 'shifts']);
            Route::post('/shifts/config', [SettingController::class, 'saveShifts']);
            Route::get('/shifts/days', [ShiftDayController::class, 'index']);
            Route::put('/shifts/days/{date}', [ShiftDayController::class, 'save']);
            Route::delete('/shifts/days/{date}', [ShiftDayController::class, 'destroy']);
            Route::get('/payroll/periods', [PayrollController::class, 'periods']);
            Route::get('/payroll/live-summary', [PayrollController::class, 'summary']);
            Route::get('/payroll/worker-evidence', [PayrollController::class, 'evidence']);
            Route::get('/payroll/pending-carry-over', [PayrollController::class, 'pendingCarryOver']);
            Route::post('/payroll/status', [PayrollController::class, 'status']);
            Route::post('/studio-recordings/sync-now', [SyncController::class, 'requestSync']);
            Route::post('/studio-recordings/sync-1618', [SyncController::class, 'sync1618']);
            Route::get('/studio-recordings/1618-status', [SyncController::class, 'status1618']);
            Route::get('/studio-recordings/sync-status', [SyncController::class, 'status']);
            Route::post('/studio-recordings/auto-sync-toggle', [SyncController::class, 'toggle']);
            Route::get('/finance/settings', [SettingController::class, 'finance']);
            Route::post('/finance/settings', [SettingController::class, 'saveFinance']);
        });
    });
});
