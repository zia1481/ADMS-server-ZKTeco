@extends('layouts.app')

@section('content')
@include('layouts.partials.page-header', [
    'title' => 'Areas',
    'subtitle' => 'Organize devices by physical location or zone.',
])

<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-plus-circle me-1"></i>Add Area
    </div>
    <div class="card-body">
        <form action="{{ route('areas.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                @if(auth()->user()->isSuperAdmin() && !current_company_id())
                    <div class="col-md-3">
                        <label for="company_id" class="form-label">Company <span class="required-mark">*</span></label>
                        <select class="form-select" id="company_id" name="company_id" required>
                            <option value="">-- Select --</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3">
                    <label for="name" class="form-label">Name <span class="required-mark">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Front Office" required>
                </div>
                <div class="col-md-3">
                    <label for="code" class="form-label">Code</label>
                    <input type="text" class="form-control" id="code" name="code" placeholder="Optional">
                </div>
                <div class="col-md-3">
                    <label for="description" class="form-label">Description</label>
                    <input type="text" class="form-control" id="description" name="description" placeholder="Optional">
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button type="reset" class="btn btn-light me-2">Reset</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Create Area
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card table-section">
    <div class="card-header">
        <i class="bi bi-table me-1"></i>Area List
        <div class="card-header-actions">
            <input type="text" class="form-control form-control-sm" placeholder="Search areas..."
                data-filter-table="#areasTable">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered" id="areasTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Description</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($areas as $area)
                    <tr>
                        <td class="fw-semibold">{{ $area->name }}</td>
                        <td><span class="badge text-bg-light border">{{ $area->code }}</span></td>
                        <td>{{ $area->description }}</td>
                        <td class="text-end">
                            <div class="table-row-actions justify-content-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#editArea{{ $area->id }}">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <form action="{{ route('areas.destroy', $area->id) }}" method="POST"
                                    data-confirm="Delete this area?">
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
                        <td colspan="4">
                            @include('layouts.partials.empty-state', [
                                'icon' => 'bi-geo-alt',
                                'title' => 'No areas found',
                            ])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($areas->hasPages())
        <div class="pagination-wrapper">
            {{ $areas->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@foreach($areas as $area)
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
                            <label class="form-label">Name <span class="required-mark">*</span></label>
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
