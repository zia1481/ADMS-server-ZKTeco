<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PendingDevice;
use App\Models\User;

class DashboardController extends Controller
{
    public function superAdmin()
    {
        $data['totalCompanies'] = Company::count();
        $data['totalPendingDevices'] = PendingDevice::where('state', PendingDevice::STATE_DETECTED)->count();
        $data['totalCompanyAdmins'] = User::where('role', User::ROLE_COMPANY_ADMIN)->count();
        $data['companies'] = Company::orderBy('name')->get();

        return view('dashboard.super-admin', $data);
    }
}
