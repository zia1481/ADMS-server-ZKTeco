@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Employees</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <hr>

    <form action="{{ route('employees.store') }}" method="POST" class="container border rounded p-4 bg-light mb-4">
        @csrf
        <div class="row mb-3">
            @if(auth()->user()->isSuperAdmin() && !current_company_id())
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="company_id" class="form-label">Company</label>
                        <select class="form-control" id="company_id" name="company_id" required>
                            <option value="">-- Select --</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif
            <div class="col-md-2">
                <div class="form-group">
                    <label for="employee_id" class="form-label">Employee ID</label>
                    <input type="number" class="form-control" id="employee_id" name="employee_id" placeholder="Device ID" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Full Name" required>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="department_id" class="form-label">Department</label>
                    <select class="form-control" id="department_id" name="department_id">
                        <option value="">-- None --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="position" class="form-label">Position</label>
                    <input type="text" class="form-control" id="position" name="position" placeholder="Optional">
                </div>
            </div>
            <div class="col-md-1">
                <div class="form-group">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" placeholder="Optional">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Optional">
                </div>
            </div>
        </div>
        <div class="d-flex">
            <div class="ms-auto">
                <button type="submit" class="btn btn-primary">Create / Update Employee</button>
            </div>
        </div>
    </form>

    <form method="GET" action="{{ route('employees.index') }}" class="row mb-3">
        <div class="col-md-3">
            <input type="text" name="employee_id" class="form-control" placeholder="Search Employee ID" value="{{ request('employee_id') }}">
        </div>
        <div class="col-md-3">
            <input type="text" name="name" class="form-control" placeholder="Search Name" value="{{ request('name') }}">
        </div>
        <div class="col-md-3">
            <select name="department_id" class="form-control">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected(request('department_id') == $dept->id)>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Department</th>
                <th>Position</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $employee)
                <tr>
                    <td>{{ $employee->employee_id }}</td>
                    <td>{{ $employee->name }}</td>
                    <td>{{ $employee->department->name ?? '-' }}</td>
                    <td>{{ $employee->position }}</td>
                    <td class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                            data-bs-target="#editEmp{{ $employee->id }}">Edit</button>
                        <form action="{{ route('employees.destroy', $employee->id) }}" method="POST"
                            onsubmit="return confirm('Delete this employee?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>

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
                                        <label class="form-label">Name</label>
                                        <input type="text" name="name" class="form-control" value="{{ $employee->name }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Department</label>
                                        <select name="department_id" class="form-control">
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
        {{ $employees->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
