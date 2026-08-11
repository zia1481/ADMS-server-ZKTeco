@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Departments / Sections</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <hr>

    <form action="{{ route('departments.store') }}" method="POST" class="container border rounded p-4 bg-light mb-4">
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
            <div class="col-md-3">
                <div class="form-group">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Engineering" required>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="code" class="form-label">Code</label>
                    <input type="text" class="form-control" id="code" name="code" placeholder="Optional">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="parent_id" class="form-label">Parent Section</label>
                    <select class="form-control" id="parent_id" name="parent_id">
                        <option value="">-- None --</option>
                        @foreach($allDepartments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <input type="text" class="form-control" id="description" name="description" placeholder="Optional">
                </div>
            </div>
        </div>
        <div class="d-flex">
            <div class="ms-auto">
                <button type="submit" class="btn btn-primary">Create Department</button>
            </div>
        </div>
    </form>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Parent</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($departments as $department)
                <tr>
                    <td>{{ $department->name }}</td>
                    <td>{{ $department->code }}</td>
                    <td>{{ $department->parent->name ?? '-' }}</td>
                    <td>{{ $department->description }}</td>
                    <td class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                            data-bs-target="#editDept{{ $department->id }}">Edit</button>
                        <form action="{{ route('departments.destroy', $department->id) }}" method="POST"
                            onsubmit="return confirm('Delete this department?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>

                <div class="modal fade" id="editDept{{ $department->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('departments.update', $department->id) }}" method="POST">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Department</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" name="name" class="form-control" value="{{ $department->name }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Code</label>
                                        <input type="text" name="code" class="form-control" value="{{ $department->code }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Parent Section</label>
                                        <select name="parent_id" class="form-control">
                                            <option value="">-- None --</option>
                                            @foreach($allDepartments as $dept)
                                                @if($dept->id !== $department->id)
                                                    <option value="{{ $dept->id }}" @selected($dept->id === $department->parent_id)>{{ $dept->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <input type="text" name="description" class="form-control" value="{{ $department->description }}">
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
        {{ $departments->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
