@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>{{ $lable }}</h2>
        <p class="text-muted">Devices that contacted the server but are not yet assigned to a company/area. Assign them to start accepting their data.</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <hr>

        <form method="GET" action="{{ route('devices.pending') }}" class="row mb-3">
            <div class="col-md-4">
                <select name="state" class="form-control">
                    <option value="">All States</option>
                    <option value="detected" @selected(request('state') === 'detected')>Detected</option>
                    <option value="assigned" @selected(request('state') === 'assigned')>Assigned</option>
                    <option value="blocked" @selected(request('state') === 'blocked')>Blocked</option>
                    <option value="ignored" @selected(request('state') === 'ignored')>Ignored</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Serial Number</th>
                    <th>IP Address</th>
                    <th>Model</th>
                    <th>First Seen</th>
                    <th>Last Seen</th>
                    <th>State</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($log as $d)
                    <tr>
                        <td>{{ $d->sn }}</td>
                        <td>{{ $d->ip_address ?? '-' }}</td>
                        <td>{{ $d->model ?? '-' }}</td>
                        <td>{{ $d->first_seen }}</td>
                        <td>{{ $d->last_seen }}</td>
                        <td>
                            @if($d->state === 'detected')
                                <span class="badge bg-warning text-dark">Detected</span>
                            @elseif($d->state === 'assigned')
                                <span class="badge bg-success">Assigned</span>
                            @elseif($d->state === 'blocked')
                                <span class="badge bg-danger">Blocked</span>
                            @else
                                <span class="badge bg-secondary">Ignored</span>
                            @endif
                        </td>
                        <td>
                            @if($d->state === 'detected')
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#assignDevice{{ $d->id }}">Assign</button>
                                <form action="{{ route('devices.ignorePending', $d->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-secondary">Ignore</button>
                                </form>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>

                    <div class="modal fade" id="assignDevice{{ $d->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('devices.assignPending') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="sn" value="{{ $d->sn }}">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Assign Device {{ $d->sn }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Company</label>
                                            <select name="company_id" class="form-control" required>
                                                <option value="">-- Select Company --</option>
                                                @foreach($companies as $company)
                                                    <option value="{{ $company->id }}" @selected(current_company_id() === $company->id)>{{ $company->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Area</label>
                                            <select name="area_id" class="form-control area-select">
                                                <option value="">-- None --</option>
                                                @foreach($areas as $area)
                                                    <option value="{{ $area->id }}" data-company="{{ $area->company_id }}" @if(str_starts_with($area->name, 'Default')) data-default="1" @endif>{{ $area->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Assign</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No pending devices.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">
            {{ $log->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.modal').forEach(function (modal) {
            const companySelect = modal.querySelector('select[name="company_id"]');
            const areaSelect = modal.querySelector('select.area-select');

            if (!companySelect || !areaSelect) return;

            function filterAreas() {
                const companyId = companySelect.value;
                let defaultOption = null;
                areaSelect.querySelectorAll('option').forEach(function (opt) {
                    if (!opt.value) return;
                    opt.hidden = companyId && opt.dataset.company !== companyId;
                    if (!opt.hidden && opt.dataset.default) {
                        defaultOption = opt;
                    }
                    if (opt.hidden && opt.selected) {
                        opt.selected = false;
                        areaSelect.value = '';
                    }
                });
                if (companyId && !areaSelect.value && defaultOption) {
                    defaultOption.selected = true;
                }
            }

            companySelect.addEventListener('change', filterAreas);
        });
    });
</script>
@endsection
