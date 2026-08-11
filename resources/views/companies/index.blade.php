@extends('layouts.app')

@section('content')
@include('layouts.partials.page-header', [
    'title' => 'Companies',
    'subtitle' => 'Manage companies and their tenant data.',
])

<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-plus-circle me-1"></i>Add Company
    </div>
    <div class="card-body">
        <form action="{{ route('companies.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="name" class="form-label">Name <span class="required-mark">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter Company Name" required>
                </div>
                <div class="col-md-4">
                    <label for="code" class="form-label">Code <span class="required-mark">*</span></label>
                    <input type="text" class="form-control" id="code" name="code" placeholder="e.g. ACME" required>
                </div>
                <div class="col-md-4">
                    <label for="description" class="form-label">Description</label>
                    <input type="text" class="form-control" id="description" name="description" placeholder="Optional">
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button type="reset" class="btn btn-light me-2">Reset</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Create Company
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card table-section">
    <div class="card-header">
        <i class="bi bi-table me-1"></i>Company List
        <div class="card-header-actions">
            <input type="text" class="form-control form-control-sm" placeholder="Search companies..."
                data-filter-table="#companiesTable">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered" id="companiesTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($companies as $company)
                    <tr>
                        <td class="fw-semibold">{{ $company->name }}</td>
                        <td><span class="badge text-bg-light border">{{ $company->code }}</span></td>
                        <td>{{ $company->description }}</td>
                        <td>
                            @include('layouts.partials.status-badge', ['status' => $company->is_active ? 'active' : 'inactive'])
                        </td>
                        <td class="text-end">
                            <div class="table-row-actions justify-content-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#editCompany{{ $company->id }}">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <form action="{{ route('companies.switch', $company->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-briefcase"></i> Work On
                                    </button>
                                </form>
                                <form action="{{ route('companies.destroy', $company->id) }}" method="POST"
                                    data-confirm="Delete this company? Its data will be removed.">
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
                                'icon' => 'bi-buildings',
                                'title' => 'No companies found',
                            ])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($companies->hasPages())
        <div class="pagination-wrapper">
            {{ $companies->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@foreach($companies as $company)
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
                            <label class="form-label">Name <span class="required-mark">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $company->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Code <span class="required-mark">*</span></label>
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
