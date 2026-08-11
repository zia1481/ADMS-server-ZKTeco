@php
$routeName = Route::currentRouteName() ?? '';
$labels = [
    'dashboard.super-admin' => 'Dashboard',
    'devices.Attendance' => 'Attendance Records',
    'devices.daily' => 'Daily Summary',
    'devices.monthly' => 'Monthly Summary',
    'companies.index' => 'Companies',
    'devices.pending' => 'New Devices',
    'users.index' => 'Users',
    'devices.index' => 'Devices',
    'areas.index' => 'Areas',
    'departments.index' => 'Departments',
    'shifts.index' => 'Shifts',
    'schedules.index' => 'Schedules',
    'devices.DeviceLog' => 'Device Logs',
    'devices.FingerLog' => 'Finger Logs',
    'password.change' => 'Change Password',
];
$currentLabel = $labels[$routeName] ?? 'ADMS';
$homeUrl = auth()->user()->isSuperAdmin() ? route('dashboard.super-admin') : route('devices.Attendance');
$homeLabel = auth()->user()->isSuperAdmin() ? 'Dashboard' : 'Home';
$user = Auth::user();
$initials = strtoupper(mb_substr(trim($user->name), 0, 1));
$roleLabel = ucwords(str_replace('_', ' ', $user->role));
@endphp

<header class="app-topbar">
    <div class="topbar-left">
        <button class="btn btn-icon topbar-hamburger" id="sidebarToggle" type="button" aria-label="Toggle navigation">
            <i class="bi bi-list"></i>
        </button>
        <nav aria-label="breadcrumb" class="d-none d-md-block">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ $homeUrl }}"><i class="bi bi-house-door me-1"></i>{{ $homeLabel }}</a>
                </li>
                <li class="breadcrumb-item active">{{ $currentLabel }}</li>
            </ol>
        </nav>
    </div>

    <div class="topbar-right">
        @if(auth()->user()->isSuperAdmin())
            <div class="dropdown">
                <button class="company-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-buildings"></i>
                    <span>{{ \App\Helpers\CompanyHelper::currentCompanyName() }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <form action="{{ route('companies.switch', 0) }}" method="POST" class="d-none" id="company-all-form">
                            @csrf
                        </form>
                        <button type="button" class="dropdown-item" onclick="event.preventDefault(); document.getElementById('company-all-form').submit();">
                            <i class="bi bi-globe2 me-2"></i>All Companies
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    @foreach (\App\Models\Company::orderBy('name')->get() as $company)
                        <li>
                            <form action="{{ route('companies.switch', $company->id) }}" method="POST" class="d-none" id="company-form-{{ $company->id }}">
                                @csrf
                            </form>
                            <button type="button" class="dropdown-item" onclick="event.preventDefault(); document.getElementById('company-form-{{ $company->id }}').submit();">
                                <i class="bi bi-building me-2"></i>{{ $company->name }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="dropdown">
            <a href="#" class="user-menu dropdown-toggle" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar">{{ $initials }}</span>
                <span class="user-meta">
                    <span class="user-name">{{ $user->name }}</span>
                    <span class="user-role">{{ $roleLabel }}</span>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="{{ route('password.change') }}"><i class="bi bi-key me-2"></i>Change Password</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
