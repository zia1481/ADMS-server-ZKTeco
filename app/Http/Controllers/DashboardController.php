<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Company;
use App\Models\Department;
use App\Models\Device;
use App\Models\Employee;
use App\Models\PendingDevice;
use App\Models\Shift;
use App\Models\User;

class DashboardController extends Controller
{
    public function superAdmin()
    {
        $data['totalCompanies'] = Company::count();
        $data['totalPendingDevices'] = PendingDevice::where('state', PendingDevice::STATE_DETECTED)->count();
        $data['totalCompanyAdmins'] = User::where('role', User::ROLE_COMPANY_ADMIN)->count();
        $data['totalDevices'] = Device::count();
        $data['totalAreas'] = Area::count();
        $data['totalDepartments'] = Department::count();
        $data['totalEmployees'] = Employee::count();
        $data['totalShifts'] = Shift::count();
        $data['companies'] = Company::orderBy('name')->get();

        return view('dashboard.super-admin', $data);
    }
}
