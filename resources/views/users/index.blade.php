@extends('layouts.app')

@section('content')
@include('layouts.partials.page-header', [
    'title' => 'Users',
    'subtitle' => 'Manage system users and their roles.',
])

<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-person-plus me-1"></i>Add User
    </div>
    <div class="card-body">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            <div class="row g-3 mb-2">
                <div class="col-md-6">
                    <label for="name" class="form-label">Name <span class="required-mark">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter Name" required>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email <span class="required-mark">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter Email" required>
                </div>
                <div class="col-md-6">
                    <label for="password" class="form-label">Password <span class="required-mark">*</span></label>
                    <input type="password" class="form-control" id="password" name="password"
                        placeholder="Enter Password" required>
                </div>
                <div class="col-md-6">
                    <label for="role" class="form-label">Role <span class="required-mark">*</span></label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="company_admin">Company Admin</option>
                        @if(!auth()->user()->isSuperAdmin())
                            <option value="viewer">Viewer</option>
                        @endif
                    </select>
                </div>
                @if(auth()->user()->isSuperAdmin())
                    <div class="col-md-6" id="company-row">
                        <label for="company_id" class="form-label">Company <span class="required-mark">*</span></label>
                        <select class="form-select" id="company_id" name="company_id" required>
                            <option value="">-- Select Company --</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" @selected(current_company_id() === $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button type="reset" class="btn btn-light me-2">Reset</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Create User
                </button>
            </div>
        </form>
    </div>
</div>

@php
$roleBadge = [
    'super_admin' => 'text-bg-danger',
    'company_admin' => 'text-bg-primary',
    'viewer' => 'text-bg-info',
];
@endphp

<div class="card table-section">
    <div class="card-header">
        <i class="bi bi-table me-1"></i>User List
        <div class="card-header-actions">
            <input type="text" class="form-control form-control-sm" placeholder="Search users..."
                data-filter-table="#usersTable">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered" id="usersTable">
            <thead>
                <tr>
                    <th>Admin Name</th>
                    <th>Employee Email</th>
                    <th>Company</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td class="fw-semibold">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->company?->name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $roleBadge[$user->role] ?? 'text-bg-secondary' }}">
                                {{ ucwords(str_replace('_', ' ', $user->role)) }}
                            </span>
                        </td>
                        <td>
                            @if($user->is_active)
                                <span class="badge text-bg-success">Active</span>
                            @else
                                <span class="badge text-bg-secondary">Disabled</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#resetPassword{{ $user->id }}">
                                <i class="bi bi-key"></i> Reset Password
                            </button>
                            <form action="{{ route('users.toggleStatus', $user->id) }}" method="POST" class="d-inline"
                                data-confirm="{{ $user->is_active ? 'Disable this user? They will not be able to log in.' : 'Enable this user?' }}">
                                @csrf
                                @if($user->is_active)
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-person-slash"></i> Disable
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-person-check"></i> Enable
                                    </button>
                                @endif
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="6">
                            @include('layouts.partials.empty-state', [
                                'icon' => 'bi-person-gear',
                                'title' => 'No users found',
                            ])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($users as $user)
    <div class="modal fade" id="resetPassword{{ $user->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('users.resetPassword', $user->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Reset Password — {{ $user->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="password{{ $user->id }}" class="form-label">New Password <span class="required-mark">*</span></label>
                            <input type="password" class="form-control" id="password{{ $user->id }}" name="password"
                                placeholder="Enter New Password" required>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmation{{ $user->id }}" class="form-label">Confirm Password <span class="required-mark">*</span></label>
                            <input type="password" class="form-control" id="password_confirmation{{ $user->id }}"
                                name="password_confirmation" placeholder="Confirm New Password" required>
                        </div>
                        <div class="form-text text-warning">
                            <i class="bi bi-info-circle me-1"></i>This user will be forced to change their password on next login.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-key me-1"></i>Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const role = document.getElementById('role');
        const companyRow = document.getElementById('company-row');
        const companySelect = document.getElementById('company_id');

        function toggleCompany() {
            if (companyRow) {
                companyRow.style.display = role.value === 'super_admin' ? 'none' : '';
            }
            if (companySelect && role.value === 'super_admin') {
                companySelect.value = '';
            }
        }

        if (role) {
            role.addEventListener('change', toggleCompany);
            toggleCompany();
        }
    });
</script>
@endsection
