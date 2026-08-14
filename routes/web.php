<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\iclockController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/change-password', [App\Http\Controllers\UserController::class, 'showChangePasswordForm'])->name('password.change');
    Route::post('/change-password', [App\Http\Controllers\UserController::class, 'changePassword'])->name('admin-password.update');
});

Route::middleware(['auth', 'changePwd'])->group(function () {
    Route::get('/', function () {
        if (auth()->user()->isSuperAdmin()) {
            return redirect('admin');
        }

        return redirect('attendance');
    });

    Route::get('attendance', [DeviceController::class, 'Attendance'])->name('devices.Attendance');
    Route::get('attendance/data', [DeviceController::class, 'getAttendance'])->name('devices.getAttendance');

    Route::get('daily', [DeviceController::class, 'daily'])->name('devices.daily');
    Route::get('daily/data', [DeviceController::class, 'getDailyAttendanceSummary'])->name('devices.getDailyAttendanceSummary');
    Route::get('monthly', [DeviceController::class, 'monthly'])->name('devices.monthly');
    Route::get('monthly/data', [DeviceController::class, 'getMonthlyAttendanceSummary'])->name('devices.getMonthlyAttendanceSummary');

    Route::middleware(['role:company_admin'])->group(function () {
        Route::get('devices', [DeviceController::class, 'Index'])->name('devices.index');
        Route::post('devices/{device}', [DeviceController::class, 'update'])->name('devices.update');

        Route::get('areas', [AreaController::class, 'index'])->name('areas.index');
        Route::post('areas', [AreaController::class, 'store'])->name('areas.store');
        Route::post('areas/{area}', [AreaController::class, 'update'])->name('areas.update');
        Route::delete('areas/{area}', [AreaController::class, 'destroy'])->name('areas.destroy');

        Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::post('departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        Route::get('shifts', [ShiftController::class, 'index'])->name('shifts.index');
        Route::post('shifts', [ShiftController::class, 'store'])->name('shifts.store');
        Route::post('shifts/{shift}', [ShiftController::class, 'update'])->name('shifts.update');
        Route::delete('shifts/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy');

        Route::get('schedules', [ScheduleController::class, 'index'])->name('schedules.index');
        Route::post('schedules', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::post('schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
    });

    Route::middleware(['role:super_admin'])->group(function () {
        Route::post('employees/import/test', [EmployeeController::class, 'importTest'])->name('employees.import.test');
        Route::post('employees/import', [EmployeeController::class, 'import'])->name('employees.import');
        Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    });

    Route::middleware(['role:company_admin,super_admin'])->group(function () {
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::post('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    });

    Route::middleware(['role:super_admin,company_admin'])->group(function () {
        Route::get('users', [UserController::class, 'Index'])->name('users.index');
        Route::post('users', [UserController::class, 'Store'])->name('users.store');
        Route::post('users/{id}/toggle-status', [UserController::class, 'toggleActive'])->name('users.toggleStatus');
        Route::post('users/{id}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword');
    });

    Route::middleware(['role:super_admin'])->group(function () {
        Route::get('admin', [App\Http\Controllers\DashboardController::class, 'superAdmin'])->name('dashboard.super-admin');

        Route::get('devices-pending', [DeviceController::class, 'pending'])->name('devices.pending');
        Route::post('devices-pending/assign', [DeviceController::class, 'assignPending'])->name('devices.assignPending');
        Route::post('devices-pending/{id}/ignore', [DeviceController::class, 'ignorePending'])->name('devices.ignorePending');
        Route::post('devices-pending/{id}/unassign', [DeviceController::class, 'unassignPending'])->name('devices.unassignPending');
        Route::post('devices-pending/{id}/disable', [DeviceController::class, 'disablePending'])->name('devices.disablePending');
        Route::post('devices-pending/{id}/enable', [DeviceController::class, 'enablePending'])->name('devices.enablePending');
        Route::post('devices-pending/{id}/redetect', [DeviceController::class, 'redetectPending'])->name('devices.redetectPending');

        Route::get('devices-log', [DeviceController::class, 'DeviceLog'])->name('devices.DeviceLog');
        Route::get('finger-log', [DeviceController::class, 'FingerLog'])->name('devices.FingerLog');

        Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::post('companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::delete('companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
        Route::post('companies/switch/{company?}', [CompanyController::class, 'switch'])->name('companies.switch');
    });
});

Auth::routes(['register' => false]);

Route::get('/home', function () {
    if (auth()->user() && auth()->user()->isSuperAdmin()) {
        return redirect('/admin');
    }

    return redirect('/attendance');
});

/************************************************************************************************************/
//                                Restricted area don't remove or update                                    //
/************************************************************************************************************/

// Restriste
// Device endpoints are stateless polls from ZKTeco terminals — skip the
// session/cookie/CSRF middleware so every poll stays lightweight.
Route::withoutMiddleware([
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
])->group(function () {
    Route::get('/iclock/cdata', [iclockController::class, 'handshake']);
    Route::post('/iclock/cdata', [iclockController::class, 'receiveRecords']);

    Route::get('/iclock/test', [iclockController::class, 'test']);
    Route::get('/iclock/getrequest', [iclockController::class, 'getrequest']);
});
