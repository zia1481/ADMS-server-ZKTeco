@extends('layouts.app')

@section('content')
@include('layouts.partials.page-header', [
    'title' => $lable,
    'subtitle' => 'Manage attendance devices connected to the server.',
])

<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-funnel me-1"></i>Filters
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('devices.index') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search Serial / Name"
                    value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="registered" @selected(request('status') === 'registered')>Registered</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="blocked" @selected(request('status') === 'blocked')>Blocked</option>
                </select>
            </div>
            <div class="col-md-5 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('devices.index') }}" class="btn btn-light">
                        <i class="bi bi-x-lg me-1"></i>Clear
                    </a>
                @endif
                @if(auth()->user()->isSuperAdmin())
                    <a href="{{ route('devices.pending') }}" class="btn btn-warning ms-auto">
                        <i class="bi bi-usb-plug me-1"></i>New Devices
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card table-section">
    <div class="card-header">
        <i class="bi bi-table me-1"></i>Device List
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered" id="devicesTable">
            <thead>
                <tr>
                    <th>Serial Number</th>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Area</th>
                    <th>IP Address</th>
                    <th>Model</th>
                    <th>Status</th>
                    <th>Online</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($log as $d)
                    <tr>
                        <td><span class="badge text-bg-light border">{{ $d->no_sn }}</span></td>
                        <td class="fw-semibold">{{ $d->nama }}</td>
                        <td>{{ $d->company_name ?? '-' }}</td>
                        <td>{{ $d->area_name ?? '-' }}</td>
                        <td><span class="font-monospace small">{{ $d->ip_address ?? '-' }}</span></td>
                        <td>{{ $d->model ?? '-' }}</td>
                        <td>
                            @include('layouts.partials.status-badge', ['status' => $d->status])
                        </td>
                        <td>
                            @if($d->online)
                                <span class="small">{{ $d->online }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#editDevice{{ $d->id }}">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="9">
                            @include('layouts.partials.empty-state', [
                                'icon' => 'bi-hdd-network',
                                'title' => 'No devices found',
                            ])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($log->hasPages())
        <div class="pagination-wrapper">
            {{ $log->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@foreach($log as $d)
    <div class="modal fade" id="editDevice{{ $d->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('devices.update', $d->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Device {{ $d->no_sn }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="nama" class="form-control" value="{{ $d->nama }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Area</label>
                            <select name="area_id" class="form-select">
                                <option value="">-- None --</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}" @selected($area->id === $d->area_id)>{{ $area->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="registered" @selected($d->status === 'registered')>Registered</option>
                                <option value="pending" @selected($d->status === 'pending')>Pending</option>
                                <option value="blocked" @selected($d->status === 'blocked')>Blocked</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection
