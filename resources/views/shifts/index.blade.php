@extends('layouts.app')

@section('content')
@include('layouts.partials.page-header', [
    'title' => 'Shifts',
    'subtitle' => 'Define work shifts, break periods and grace allowances.',
])

<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-plus-circle me-1"></i>Add Shift
    </div>
    <div class="card-body">
        <form action="{{ route('shifts.store') }}" method="POST">
            @csrf
            <div class="row g-3 mb-2">
                @if(auth()->user()->isSuperAdmin() && !current_company_id())
                    <div class="col-md-3">
                        <label for="company_id" class="form-label">Company <span class="required-mark">*</span></label>
                        <select class="form-select" id="company_id" name="company_id" required>
                            <option value="">-- Select Company --</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3">
                    <label for="name" class="form-label">Name <span class="required-mark">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Morning" required>
                </div>
                <div class="col-md-2">
                    <label for="start_time" class="form-label">Start Time <span class="required-mark">*</span></label>
                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                </div>
                <div class="col-md-2">
                    <label for="end_time" class="form-label">End Time <span class="required-mark">*</span></label>
                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                </div>
                <div class="col-md-2">
                    <label for="code" class="form-label">Code</label>
                    <input type="text" class="form-control" id="code" name="code" placeholder="Optional">
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="break_start" class="form-label">Break Start</label>
                    <input type="time" class="form-control" id="break_start" name="break_start">
                </div>
                <div class="col-md-3">
                    <label for="break_end" class="form-label">Break End</label>
                    <input type="time" class="form-control" id="break_end" name="break_end">
                </div>
                <div class="col-md-3">
                    <label for="grace_late_minutes" class="form-label">Late Grace (min)</label>
                    <input type="number" min="0" class="form-control" id="grace_late_minutes" name="grace_late_minutes" value="0">
                </div>
                <div class="col-md-3">
                    <label for="grace_early_leave_minutes" class="form-label">Early Leave Grace (min)</label>
                    <input type="number" min="0" class="form-control" id="grace_early_leave_minutes" name="grace_early_leave_minutes" value="0">
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-end mt-3">
                <div class="form-check me-3">
                    <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" checked>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
                <button type="reset" class="btn btn-light me-2">Reset</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Create Shift
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card table-section">
    <div class="card-header">
        <i class="bi bi-table me-1"></i>Shift List
        <div class="card-header-actions">
            <input type="text" class="form-control form-control-sm" placeholder="Search shifts..."
                data-filter-table="#shiftsTable">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered" id="shiftsTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Break</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shifts as $shift)
                    <tr>
                        <td class="fw-semibold">{{ $shift->name }}</td>
                        <td><span class="badge text-bg-light border">{{ $shift->code }}</span></td>
                        <td>{{ $shift->start_time }}</td>
                        <td>{{ $shift->end_time }}</td>
                        <td>{{ $shift->break_start ? $shift->break_start . ' - ' . $shift->break_end : '-' }}</td>
                        <td>
                            @include('layouts.partials.status-badge', ['status' => $shift->is_active ? 'active' : 'inactive'])
                        </td>
                        <td class="text-end">
                            <div class="table-row-actions justify-content-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#editShift{{ $shift->id }}">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <form action="{{ route('shifts.destroy', $shift->id) }}" method="POST"
                                    data-confirm="Delete this shift?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="7">
                            @include('layouts.partials.empty-state', [
                                'icon' => 'bi-clock-history',
                                'title' => 'No shifts found',
                            ])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($shifts->hasPages())
        <div class="pagination-wrapper">
            {{ $shifts->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@foreach($shifts as $shift)
    <div class="modal fade" id="editShift{{ $shift->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('shifts.update', $shift->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Shift</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name <span class="required-mark">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $shift->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" value="{{ $shift->code }}">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label">Start Time <span class="required-mark">*</span></label>
                                <input type="time" name="start_time" class="form-control" value="{{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">End Time <span class="required-mark">*</span></label>
                                <input type="time" name="end_time" class="form-control" value="{{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label">Break Start</label>
                                <input type="time" name="break_start" class="form-control" value="{{ $shift->break_start ? \Carbon\Carbon::parse($shift->break_start)->format('H:i') : '' }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Break End</label>
                                <input type="time" name="break_end" class="form-control" value="{{ $shift->break_end ? \Carbon\Carbon::parse($shift->break_end)->format('H:i') : '' }}">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label">Late Grace (min)</label>
                                <input type="number" min="0" name="grace_late_minutes" class="form-control" value="{{ $shift->grace_late_minutes }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Early Leave Grace (min)</label>
                                <input type="number" min="0" name="grace_early_leave_minutes" class="form-control" value="{{ $shift->grace_early_leave_minutes }}">
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                id="shift_active{{ $shift->id }}" @checked($shift->is_active)>
                            <label class="form-check-label" for="shift_active{{ $shift->id }}">Active</label>
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
