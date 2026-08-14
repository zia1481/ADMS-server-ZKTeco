@php
$routeName = Route::currentRouteName() ?? '';
$active = function (...$names) use ($routeName) {
    foreach ($names as $name) {
        if ($name === $routeName) return true;
        if (str_ends_with($name, '.*') && str_starts_with($routeName, substr($name, 0, -1))) return true;
    }
    return false;
};
@endphp

<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <img src="/logo-light.svg" alt="ADMS" class="sidebar-brand-logo">
        <span class="sidebar-brand-text">ADMS</span>
    </div>

    <nav class="sidebar-nav">
        @if(auth()->user()->isSuperAdmin())
            <div class="nav-section">Dashboard</div>
            <a href="{{ route('dashboard.super-admin') }}"
                class="sidebar-link {{ $active('dashboard.super-admin') ? 'active' : '' }}"
                title="Dashboard" data-bs-toggle="tooltip" data-bs-placement="right">
                <i class="bi bi-speedometer2"></i>
                <span class="sidebar-link-text">Dashboard</span>
            </a>
        @endif

        <div class="nav-section">Attendance</div>
        <a href="{{ route('devices.Attendance') }}"
            class="sidebar-link {{ $active('devices.Attendance') ? 'active' : '' }}"
            title="Attendance Records" data-bs-toggle="tooltip" data-bs-placement="right">
            <i class="bi bi-calendar2-check"></i>
            <span class="sidebar-link-text">Attendance Records</span>
        </a>
        <a href="{{ route('devices.daily') }}"
            class="sidebar-link {{ $active('devices.daily') ? 'active' : '' }}"
            title="Daily Summary" data-bs-toggle="tooltip" data-bs-placement="right">
            <i class="bi bi-calendar-day"></i>
            <span class="sidebar-link-text">Daily Summary</span>
        </a>
        <a href="{{ route('devices.monthly') }}"
            class="sidebar-link {{ $active('devices.monthly') ? 'active' : '' }}"
            title="Monthly Summary" data-bs-toggle="tooltip" data-bs-placement="right">
            <i class="bi bi-calendar-month"></i>
            <span class="sidebar-link-text">Monthly Summary</span>
        </a>

        @if(auth()->user()->isCompanyAdmin())
            <div class="nav-section">Personnel</div>
            <a href="{{ route('employees.index') }}"
                class="sidebar-link {{ $active('employees.*') ? 'active' : '' }}"
                title="Employees" data-bs-toggle="tooltip" data-bs-placement="right">
                <i class="bi bi-people"></i>
                <span class="sidebar-link-text">Employees</span>
            </a>
            <a href="{{ route('departments.index') }}"
                class="sidebar-link {{ $active('departments.*') ? 'active' : '' }}"
                title="Departments" data-bs-toggle="tooltip" data-bs-placement="right">
                <i class="bi bi-diagram-3"></i>
                <span class="sidebar-link-text">Departments</span>
            </a>

            <div class="nav-section">Schedule</div>
            <a href="{{ route('shifts.index') }}"
                class="sidebar-link {{ $active('shifts.*') ? 'active' : '' }}"
                title="Shifts" data-bs-toggle="tooltip" data-bs-placement="right">
                <i class="bi bi-clock-history"></i>
                <span class="sidebar-link-text">Shifts</span>
            </a>
            <a href="{{ route('schedules.index') }}"
                class="sidebar-link {{ $active('schedules.*') ? 'active' : '' }}"
                title="Schedules" data-bs-toggle="tooltip" data-bs-placement="right">
                <i class="bi bi-calendar3"></i>
                <span class="sidebar-link-text">Schedules</span>
            </a>

            <div class="nav-section">Devices</div>
            <a href="{{ route('devices.index') }}"
                class="sidebar-link {{ $active('devices.index') ? 'active' : '' }}"
                title="Devices" data-bs-toggle="tooltip" data-bs-placement="right">
                <i class="bi bi-hdd-network"></i>
                <span class="sidebar-link-text">Devices</span>
            </a>
            <a href="{{ route('areas.index') }}"
                class="sidebar-link {{ $active('areas.*') ? 'active' : '' }}"
                title="Areas" data-bs-toggle="tooltip" data-bs-placement="right">
                <i class="bi bi-geo-alt"></i>
                <span class="sidebar-link-text">Areas</span>
            </a>
        @endif

        @if(auth()->user()->isSuperAdmin() || auth()->user()->isCompanyAdmin())
            <div class="nav-section">System</div>
            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('companies.index') }}"
                    class="sidebar-link {{ $active('companies.*') ? 'active' : '' }}"
                    title="Companies" data-bs-toggle="tooltip" data-bs-placement="right">
                    <i class="bi bi-buildings"></i>
                    <span class="sidebar-link-text">Companies</span>
                </a>
                <a href="{{ route('devices.pending') }}"
                    class="sidebar-link {{ $active('devices.pending') ? 'active' : '' }}"
                    title="New Devices" data-bs-toggle="tooltip" data-bs-placement="right">
                    <i class="bi bi-usb-plug"></i>
                    <span class="sidebar-link-text">New Devices</span>
                </a>
                <a href="{{ route('devices.DeviceLog') }}"
                    class="sidebar-link {{ $active('devices.DeviceLog') ? 'active' : '' }}"
                    title="Device Logs" data-bs-toggle="tooltip" data-bs-placement="right">
                    <i class="bi bi-receipt"></i>
                    <span class="sidebar-link-text">Device Logs</span>
                </a>
                <a href="{{ route('devices.FingerLog') }}"
                    class="sidebar-link {{ $active('devices.FingerLog') ? 'active' : '' }}"
                    title="Finger Logs" data-bs-toggle="tooltip" data-bs-placement="right">
                    <i class="bi bi-fingerprint"></i>
                    <span class="sidebar-link-text">Finger Logs</span>
                </a>
            @endif
            <a href="{{ route('users.index') }}"
                class="sidebar-link {{ $active('users.*') ? 'active' : '' }}"
                title="Users" data-bs-toggle="tooltip" data-bs-placement="right">
                <i class="bi bi-person-gear"></i>
                <span class="sidebar-link-text">Users</span>
            </a>
        @endif
    </nav>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
