@extends('layouts.app')

@section('content')
@include('layouts.partials.page-header', [
    'title' => 'Super Admin Dashboard',
    'subtitle' => 'Overview of companies, devices and administrators.',
])

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-primary"><i class="bi bi-buildings"></i></div>
            <div>
                <div class="stat-label">Companies</div>
                <div class="stat-value">{{ $totalCompanies }}</div>
                <div class="stat-sub">Registered companies</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-warning"><i class="bi bi-usb-plug"></i></div>
            <div>
                <div class="stat-label">New Devices</div>
                <div class="stat-value">{{ $totalPendingDevices }}</div>
                <div class="stat-sub">Awaiting assignment</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-success"><i class="bi bi-person-gear"></i></div>
            <div>
                <div class="stat-label">Company Admins</div>
                <div class="stat-value">{{ $totalCompanyAdmins }}</div>
                <div class="stat-sub">Admin users</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-info"><i class="bi bi-hdd-network"></i></div>
            <div>
                <div class="stat-label">Devices</div>
                <div class="stat-value">{{ $totalDevices }}</div>
                <div class="stat-sub">Registered devices</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-secondary"><i class="bi bi-geo-alt"></i></div>
            <div>
                <div class="stat-label">Areas</div>
                <div class="stat-value">{{ $totalAreas }}</div>
                <div class="stat-sub">Total areas</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-primary"><i class="bi bi-diagram-3"></i></div>
            <div>
                <div class="stat-label">Departments</div>
                <div class="stat-value">{{ $totalDepartments }}</div>
                <div class="stat-sub">Total sections</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-success"><i class="bi bi-people"></i></div>
            <div>
                <div class="stat-label">Employees</div>
                <div class="stat-value">{{ $totalEmployees }}</div>
                <div class="stat-sub">Total employees</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-info"><i class="bi bi-clock-history"></i></div>
            <div>
                <div class="stat-label">Shifts</div>
                <div class="stat-value">{{ $totalShifts }}</div>
                <div class="stat-sub">Total shifts</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-lightning-charge me-1"></i>Quick Actions</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title fs-6 fw-semibold"><i class="bi bi-buildings text-primary me-2"></i>Companies</h5>
                        <p class="card-text small text-muted mb-3">Create, edit or delete companies. A default Area is created automatically for each new company.</p>
                        <a href="{{ route('companies.index') }}" class="btn btn-sm btn-primary">Manage Companies</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title fs-6 fw-semibold"><i class="bi bi-usb-plug text-warning me-2"></i>New Devices</h5>
                        <p class="card-text small text-muted mb-3">Assign devices that contacted the server to a company and its default area.</p>
                        <a href="{{ route('devices.pending') }}" class="btn btn-sm btn-warning">Assign New Devices</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title fs-6 fw-semibold"><i class="bi bi-person-gear text-success me-2"></i>Company Admins</h5>
                        <p class="card-text small text-muted mb-3">Create company admin users. They manage their company's attendance from the Company Dashboard.</p>
                        <a href="{{ route('users.index') }}" class="btn btn-sm btn-success">Create Company Admin</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
