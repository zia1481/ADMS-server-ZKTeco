@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Shifts</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <hr>

    <form action="{{ route('shifts.store') }}" method="POST" class="container border rounded p-4 bg-light mb-4">
        @csrf
        <div class="row mb-3">
            @if(auth()->user()->isSuperAdmin() && !current_company_id())
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="company_id" class="form-label">Company</label>
                        <select class="form-control" id="company_id" name="company_id" required>
                            <option value="">-- Select Company --</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif
            <div class="col-md-2">
                <div class="form-group">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Morning" required>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="start_time" class="form-label">Start Time</label>
                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="end_time" class="form-label">End Time</label>
                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="code" class="form-label">Code</label>
                    <input type="text" class="form-control" id="code" name="code" placeholder="Optional">
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="break_start" class="form-label">Break Start</label>
                    <input type="time" class="form-control" id="break_start" name="break_start">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="break_end" class="form-label">Break End</label>
                    <input type="time" class="form-control" id="break_end" name="break_end">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="grace_late_minutes" class="form-label">Late Grace (min)</label>
                    <input type="number" min="0" class="form-control" id="grace_late_minutes" name="grace_late_minutes" value="0">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="grace_early_leave_minutes" class="form-label">Early Leave Grace (min)</label>
                    <input type="number" min="0" class="form-control" id="grace_early_leave_minutes" name="grace_early_leave_minutes" value="0">
                </div>
            </div>
        </div>
        <div class="d-flex">
            <div class="form-check me-3 align-self-center">
                <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" checked>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
            <div class="ms-auto">
                <button type="submit" class="btn btn-primary">Create Shift</button>
            </div>
        </div>
    </form>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Start</th>
                <th>End</th>
                <th>Break</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shifts as $shift)
                <tr>
                    <td>{{ $shift->name }}</td>
                    <td>{{ $shift->code }}</td>
                    <td>{{ $shift->start_time }}</td>
                    <td>{{ $shift->end_time }}</td>
                    <td>{{ $shift->break_start ? $shift->break_start . ' - ' . $shift->break_end : '-' }}</td>
                    <td>{{ $shift->is_active ? 'Active' : 'Inactive' }}</td>
                    <td class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                            data-bs-target="#editShift{{ $shift->id }}">Edit</button>
                        <form action="{{ route('shifts.destroy', $shift->id) }}" method="POST"
                            onsubmit="return confirm('Delete this shift?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>

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
                                        <label class="form-label">Name</label>
                                        <input type="text" name="name" class="form-control" value="{{ $shift->name }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Code</label>
                                        <input type="text" name="code" class="form-control" value="{{ $shift->code }}">
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label class="form-label">Start Time</label>
                                            <input type="time" name="start_time" class="form-control" value="{{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }}" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">End Time</label>
                                            <input type="time" name="end_time" class="form-control" value="{{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label class="form-label">Break Start</label>
                                            <input type="time" name="break_start" class="form-control" value="{{ $shift->break_start ? \Carbon\Carbon::parse($shift->break_start)->format('H:i') : '' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Break End</label>
                                            <input type="time" name="break_end" class="form-control" value="{{ $shift->break_end ? \Carbon\Carbon::parse($shift->break_end)->format('H:i') : '' }}">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
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
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </tbody>
    </table>
    <div class="pagination">
        {{ $shifts->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
