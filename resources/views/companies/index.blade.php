@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Companies</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('failed'))
        <div class="alert alert-danger">{{ session('failed') }}</div>
    @endif
    <hr>

    <form action="{{ route('companies.store') }}" method="POST" class="container border rounded p-4 bg-light mb-4">
        @csrf
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter Company Name" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="code" class="form-label">Code</label>
                    <input type="text" class="form-control" id="code" name="code" placeholder="e.g. ACME" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <input type="text" class="form-control" id="description" name="description" placeholder="Optional">
                </div>
            </div>
        </div>
        <div class="d-flex">
            <div class="ms-auto">
                <button type="submit" class="btn btn-primary">Create Company</button>
            </div>
        </div>
    </form>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Description</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($companies as $company)
                <tr>
                    <td>{{ $company->name }}</td>
                    <td>{{ $company->code }}</td>
                    <td>{{ $company->description }}</td>
                    <td>{{ $company->is_active ? 'Active' : 'Inactive' }}</td>
                    <td class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                            data-bs-target="#editCompany{{ $company->id }}">Edit</button>
                        <form action="{{ route('companies.switch', $company->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">Work On</button>
                        </form>
                        <form action="{{ route('companies.destroy', $company->id) }}" method="POST"
                            onsubmit="return confirm('Delete this company? Its data will be removed.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>

                <div class="modal fade" id="editCompany{{ $company->id }}" tabindex="-1"
                    aria-labelledby="editCompany{{ $company->id }}Label" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('companies.update', $company->id) }}" method="POST">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Company</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" name="name" class="form-control" value="{{ $company->name }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Code</label>
                                        <input type="text" name="code" class="form-control" value="{{ $company->code }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <input type="text" name="description" class="form-control" value="{{ $company->description }}">
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                            id="is_active{{ $company->id }}" @checked($company->is_active)>
                                        <label class="form-check-label" for="is_active{{ $company->id }}">Active</label>
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
        {{ $companies->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
