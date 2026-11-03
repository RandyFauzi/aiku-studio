<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContributorController;
use App\Http\Controllers\DailyWorkReportController;
use App\Http\Controllers\DeviceAccountController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\RecordingController;
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
    Route::get('/me', fn () => auth()->user()->role === 'admin' ? redirect('/') : view('portal.index'));
    Route::get('/api/me/attendance', [AttendanceController::class, 'mine']);
    Route::post('/api/me/attendance', [AttendanceController::class, 'checkIn'])->middleware('throttle:20,1');
    Route::get('/api/me/work-reports', [DailyWorkReportController::class, 'mine']);
    Route::put('/api/me/work-report', [DailyWorkReportController::class, 'save'])->middleware('throttle:30,1');
    Route::middleware(EnsureAdministrator::class)->group(function () {
        Route::view('/attendance', 'panel.attendance')->name('attendance');
        Route::view('/attendance.html', 'panel.attendance');
        Route::get('/api/attendance', [AttendanceController::class, 'index']);
        Route::view('/work-reports', 'panel.work-reports')->name('work-reports');
        Route::view('/work-reports.html', 'panel.work-reports');
        Route::get('/api/work-reports', [DailyWorkReportController::class, 'index']);
        Route::view('/devices', 'panel.devices')->name('devices');
        Route::view('/devices.html', 'panel.devices');
        foreach (['index', 'contributors', 'create', 'payslips', 'profit', 'recordings', 'shifts'] as $page) {
            $view = 'panel.'.$page;
            Route::view('/'.$page, $view)->name($page);
            Route::view('/'.$page.'.html', $view);
        }
        Route::get('/slip/live/{worker}', [PayslipController::class, 'live']);
        Route::get('/slip/{payslip}', [PayslipController::class, 'page'])->whereNumber('payslip');
        Route::get('/{type}/{path}', [PayslipController::class, 'file'])->where('type', 'evidence_images|pdf_slips')->where('path', '.+');

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
            Route::get('/contributors', [ContributorController::class, 'index']);
            Route::post('/contributors', [ContributorController::class, 'store']);
            Route::put('/contributors/{contributor}', [ContributorController::class, 'update']);
            Route::patch('/contributors/{contributor}/role', [ContributorController::class, 'updateRole']);
            Route::delete('/contributors/{contributor}', [ContributorController::class, 'destroy']);
            Route::post('/upload-evidence', [PayslipController::class, 'upload']);
            Route::post('/payslips', [PayslipController::class, 'store']);
            Route::get('/payslips/{payslip}', [PayslipController::class, 'show'])->whereNumber('payslip');
            Route::put('/payslips/{payslip}', [PayslipController::class, 'update']);
            Route::delete('/payslips/{payslip}', [PayslipController::class, 'destroy']);
            Route::patch('/payslips/{payslip}/status', [PayslipController::class, 'status']);
            Route::get('/payslips/{payslip}/export', [PayslipController::class, 'export']);
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
            Route::post('/payroll/status', [PayrollController::class, 'status']);
            Route::get('/payroll/export-live/{worker}', [PayslipController::class, 'exportLive']);
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
