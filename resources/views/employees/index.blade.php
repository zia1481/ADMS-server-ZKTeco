@extends('layouts.app')

@section('content')
@include('layouts.partials.page-header', [
    'title' => 'Employees',
    'subtitle' => 'Manage employee records and their attendance configuration.',
])

<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-plus-circle me-1"></i>Add / Update Employee
    </div>
    <div class="card-body">
        <form action="{{ route('employees.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                @if(auth()->user()->isSuperAdmin() && !current_company_id())
                    <div class="col-md-2">
                        <label for="company_id" class="form-label">Company <span class="required-mark">*</span></label>
                        <select class="form-select" id="company_id" name="company_id" required>
                            <option value="">-- Select --</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <label for="employee_id" class="form-label">Employee ID <span class="required-mark">*</span></label>
                    <input type="number" class="form-control" id="employee_id" name="employee_id" placeholder="Device ID" required>
                </div>
                <div class="col-md-3">
                    <label for="name" class="form-label">Name <span class="required-mark">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Full Name" required>
                </div>
                <div class="col-md-2">
                    <label for="department_id" class="form-label">Department</label>
                    <select class="form-select" id="department_id" name="department_id">
                        <option value="">-- None --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="position" class="form-label">Position</label>
                    <input type="text" class="form-control" id="position" name="position" placeholder="Optional">
                </div>
                <div class="col-md-2">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" placeholder="Optional">
                </div>
                <div class="col-md-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Optional">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Schedule Assignments</label>
                    <div id="schedule-assignments" data-schedule-assignments></div>
                    <button type="button" class="btn btn-sm btn-outline-primary"
                        data-add-schedule="#schedule-assignments">
                        <i class="bi bi-plus-lg me-1"></i>Add Schedule
                    </button>
                    <div class="help-text">Assign shifts to this employee. Effective dates are optional (blank = always).</div>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button type="reset" class="btn btn-light me-2">Reset</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1"></i>Create / Update Employee
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card table-section">
    <div class="card-header flex-wrap gap-2">
        <i class="bi bi-table me-1"></i>Employee List
        <form method="GET" action="{{ route('employees.index') }}"
            class="card-header-actions d-flex gap-2 flex-wrap">
            <input type="text" name="employee_id" class="form-control form-control-sm"
                style="width:130px" placeholder="Employee ID" value="{{ request('employee_id') }}">
            <input type="text" name="name" class="form-control form-control-sm"
                style="width:150px" placeholder="Name" value="{{ request('name') }}">
            <select name="department_id" class="form-select form-select-sm" style="width:150px">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected(request('department_id') == $dept->id)>{{ $dept->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-funnel"></i> Filter
            </button>
            @if(request()->hasAny(['employee_id', 'name', 'department_id']))
                <a href="{{ route('employees.index') }}" class="btn btn-sm btn-light">
                    <i class="bi bi-x-lg"></i> Clear
                </a>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered" id="employeesTable">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $employee)
                    <tr>
                        <td><span class="badge text-bg-light border">{{ $employee->employee_id }}</span></td>
                        <td class="fw-semibold">{{ $employee->name }}</td>
                        <td>{{ $employee->department->name ?? '-' }}</td>
                        <td>{{ $employee->position }}</td>
                        <td class="text-end">
                            <div class="table-row-actions justify-content-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#editEmp{{ $employee->id }}">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <form action="{{ route('employees.destroy', $employee->id) }}" method="POST"
                                    data-confirm="Delete this employee?">
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
                        <td colspan="5">
                            @include('layouts.partials.empty-state', [
                                'icon' => 'bi-people',
                                'title' => 'No employees found',
                            ])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())
        <div class="pagination-wrapper">
            {{ $employees->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@foreach($employees as $employee)
    <div class="modal fade" id="editEmp{{ $employee->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('employees.update', $employee->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Employee #{{ $employee->employee_id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name <span class="required-mark">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $employee->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">-- None --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected($dept->id === $employee->department_id)>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" name="position" class="form-control" value="{{ $employee->position }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ $employee->phone }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ $employee->email }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Schedule Assignments</label>
                            <div data-schedule-assignments id="schedule-assignments-{{ $employee->id }}">
                                @foreach($employee->schedules as $schedIndex => $sched)
                                    <div class="schedule-assignment-row row g-2 mb-2">
                                        <div class="col-md-6">
                                            <select class="form-select" name="schedules[{{ $schedIndex }}][schedule_id]">
                                                <option value="">-- Select Schedule --</option>
                                                @foreach($schedules as $available)
                                                    <option value="{{ $available->id }}" @selected($available->id === $sched->id)>
                                                        {{ $available->name }} ({{ $available->shift->start_time ?? '' }} - {{ $available->shift->end_time ?? '' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="date" class="form-control" name="schedules[{{ $schedIndex }}][effective_from]"
                                                value="{{ $sched->pivot->effective_from }}">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="date" class="form-control" name="schedules[{{ $schedIndex }}][effective_to]"
                                                value="{{ $sched->pivot->effective_to }}">
                                        </div>
                                        <div class="col-md-2 d-flex align-items-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-schedule-assignment">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                data-add-schedule="#schedule-assignments-{{ $employee->id }}">
                                <i class="bi bi-plus-lg me-1"></i>Add Schedule
                            </button>
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

<template id="schedule-assignment-template">
    <div class="schedule-assignment-row row g-2 mb-2">
        <div class="col-md-6">
            <select class="form-select" name="schedules[__INDEX__][schedule_id]">
                <option value="">-- Select Schedule --</option>
                @foreach($schedules as $available)
                    <option value="{{ $available->id }}">
                        {{ $available->name }} ({{ $available->shift->start_time ?? '' }} - {{ $available->shift->end_time ?? '' }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" name="schedules[__INDEX__][effective_from]">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" name="schedules[__INDEX__][effective_to]">
        </div>
        <div class="col-md-2 d-flex align-items-center">
            <button type="button" class="btn btn-sm btn-outline-danger remove-schedule-assignment">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</template>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var template = document.getElementById('schedule-assignment-template');

        function addScheduleRow(container) {
            var row = template.content.cloneNode(true).querySelector('.schedule-assignment-row');
            var index = container.querySelectorAll('.schedule-assignment-row').length;
            row.innerHTML = row.innerHTML.replace(/__INDEX__/g, index);
            container.appendChild(row);
        }

        document.querySelectorAll('[data-add-schedule]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var container = document.querySelector(btn.getAttribute('data-add-schedule'));
                if (container) {
                    addScheduleRow(container);
                }
            });
        });

        document.addEventListener('click', function (e) {
            var removeBtn = e.target.closest('.remove-schedule-assignment');
            if (removeBtn) {
                removeBtn.closest('.schedule-assignment-row').remove();
            }
        });
    });
</script>
@endsection
