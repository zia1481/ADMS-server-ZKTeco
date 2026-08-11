@extends('layouts.app')

@section('content')
@include('layouts.partials.page-header', [
    'title' => 'Schedules',
    'subtitle' => 'Assign shifts to departments with recurring working days.',
])

<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-plus-circle me-1"></i>Add Schedule
    </div>
    <div class="card-body">
        <form action="{{ route('schedules.store') }}" method="POST">
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
                    <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Weekdays Office" required>
                </div>
                <div class="col-md-3">
                    <label for="shift_id" class="form-label">Shift <span class="required-mark">*</span></label>
                    <select class="form-select" id="shift_id" name="shift_id" required>
                        <option value="">-- Select Shift --</option>
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->name }} ({{ $shift->start_time }} - {{ $shift->end_time }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="department_id" class="form-label">Department</label>
                    <select class="form-select" id="department_id" name="department_id">
                        <option value="">-- All Departments --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <div class="help-text">Leave blank to apply to all departments.</div>
                </div>
            </div>
            <div class="row g-3 mb-2">
                <div class="col-md-4">
                    <label class="form-label">Working Days</label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($weekDays as $index => $day)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="working_days[]" value="{{ $index }}"
                                    id="wd_create_{{ $index }}" @checked(in_array($index, [1, 2, 3, 4, 5]))>
                                <label class="form-check-label" for="wd_create_{{ $index }}">{{ $day }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="effective_from" class="form-label">Effective From</label>
                    <input type="date" class="form-control" id="effective_from" name="effective_from">
                </div>
                <div class="col-md-4">
                    <label for="effective_to" class="form-label">Effective To</label>
                    <input type="date" class="form-control" id="effective_to" name="effective_to">
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-end mt-3">
                <div class="form-check me-3">
                    <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" checked>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
                <button type="reset" class="btn btn-light me-2">Reset</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Create Schedule
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card table-section">
    <div class="card-header">
        <i class="bi bi-table me-1"></i>Schedule List
        <div class="card-header-actions">
            <input type="text" class="form-control form-control-sm" placeholder="Search schedules..."
                data-filter-table="#schedulesTable">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered" id="schedulesTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Shift</th>
                    <th>Department</th>
                    <th>Working Days</th>
                    <th>Effective</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schedules as $schedule)
                    <tr>
                        <td class="fw-semibold">{{ $schedule->name }}</td>
                        <td>{{ $schedule->shift->name ?? '-' }}</td>
                        <td>{{ $schedule->department->name ?? 'All' }}</td>
                        <td>
                            @php
                                $days = $schedule->working_days ?? [];
                                $names = array_map(function ($d) use ($weekDays) {
                                    return $weekDays[(int) $d] ?? $d;
                                }, $days);
                            @endphp
                            {{ implode(', ', $names) ?: '-' }}
                        </td>
                        <td>{{ $schedule->effective_from ? $schedule->effective_from->format('Y-m-d') . ' to ' . ($schedule->effective_to ? $schedule->effective_to->format('Y-m-d') : 'open') : '-' }}</td>
                        <td>
                            @include('layouts.partials.status-badge', ['status' => $schedule->is_active ? 'active' : 'inactive'])
                        </td>
                        <td class="text-end">
                            <div class="table-row-actions justify-content-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#editSchedule{{ $schedule->id }}">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <form action="{{ route('schedules.destroy', $schedule->id) }}" method="POST"
                                    data-confirm="Delete this schedule?">
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
                                'icon' => 'bi-calendar3',
                                'title' => 'No schedules found',
                            ])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($schedules->hasPages())
        <div class="pagination-wrapper">
            {{ $schedules->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@foreach($schedules as $schedule)
    <div class="modal fade" id="editSchedule{{ $schedule->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('schedules.update', $schedule->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name <span class="required-mark">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $schedule->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Shift <span class="required-mark">*</span></label>
                            <select name="shift_id" class="form-select" required>
                                @foreach($shifts as $shift)
                                    <option value="{{ $shift->id }}" @selected($shift->id === $schedule->shift_id)>{{ $shift->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">-- All Departments --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected($dept->id === $schedule->department_id)>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Working Days</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($weekDays as $index => $day)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="working_days[]" value="{{ $index }}"
                                            id="wd_edit_{{ $schedule->id }}_{{ $index }}"
                                            @checked(in_array($index, $schedule->working_days ?? []))>
                                        <label class="form-check-label" for="wd_edit_{{ $schedule->id }}_{{ $index }}">{{ $day }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label">Effective From</label>
                                <input type="date" name="effective_from" class="form-control" value="{{ $schedule->effective_from ? $schedule->effective_from->format('Y-m-d') : '' }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Effective To</label>
                                <input type="date" name="effective_to" class="form-control" value="{{ $schedule->effective_to ? $schedule->effective_to->format('Y-m-d') : '' }}">
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                id="sched_active{{ $schedule->id }}" @checked($schedule->is_active)>
                            <label class="form-check-label" for="sched_active{{ $schedule->id }}">Active</label>
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
