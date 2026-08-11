@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Areas</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <hr>

    <form action="{{ route('areas.store') }}" method="POST" class="container border rounded p-4 bg-light mb-4">
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
                    <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Main Gate" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="code" class="form-label">Code</label>
                    <input type="text" class="form-control" id="code" name="code" placeholder="Optional">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <input type="text" class="form-control" id="description" name="description" placeholder="Optional">
                </div>
            </div>
        </div>
        <div class="d-flex">
            <div class="ms-auto">
                <button type="submit" class="btn btn-primary">Create Area</button>
            </div>
        </div>
    </form>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($areas as $area)
                <tr>
                    <td>{{ $area->name }}</td>
                    <td>{{ $area->code }}</td>
                    <td>{{ $area->description }}</td>
                    <td class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                            data-bs-target="#editArea{{ $area->id }}">Edit</button>
                        <form action="{{ route('areas.destroy', $area->id) }}" method="POST"
                            onsubmit="return confirm('Delete this area?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>

                <div class="modal fade" id="editArea{{ $area->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('areas.update', $area->id) }}" method="POST">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Area</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" name="name" class="form-control" value="{{ $area->name }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Code</label>
                                        <input type="text" name="code" class="form-control" value="{{ $area->code }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <input type="text" name="description" class="form-control" value="{{ $area->description }}">
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
        {{ $areas->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
