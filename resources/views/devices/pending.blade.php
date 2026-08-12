@extends('layouts.app')

@section('content')
@include('layouts.partials.page-header', [
    'title' => $lable,
    'subtitle' => 'Devices that contacted the server but are not yet assigned to a company/area. Assign them to start accepting their data.',
])

<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-funnel me-1"></i>Filters
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('devices.pending') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <select name="state" class="form-select">
                    <option value="">All States</option>
                    <option value="detected" @selected(request('state') === 'detected')>Detected</option>
                    <option value="assigned" @selected(request('state') === 'assigned')>Assigned</option>
                    <option value="blocked" @selected(request('state') === 'blocked')>Blocked</option>
                    <option value="ignored" @selected(request('state') === 'ignored')>Ignored</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                @if(request('state'))
                    <a href="{{ route('devices.pending') }}" class="btn btn-light">
                        <i class="bi bi-x-lg me-1"></i>Clear
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card table-section">
    <div class="card-header">
        <i class="bi bi-table me-1"></i>Pending Devices
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered" id="pendingDevicesTable">
            <thead>
                <tr>
                    <th>Serial Number</th>
                    <th>IP Address</th>
                    <th>Model</th>
                    <th>Comm Key</th>
                    <th>First Seen</th>
                    <th>Last Seen</th>
                    <th>State</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($log as $d)
                    <tr>
                        <td><span class="badge text-bg-light border">{{ $d->sn }}</span></td>
                        <td><span class="font-monospace small">{{ $d->ip_address ?? '-' }}</span></td>
                        <td>{{ $d->model ?? '-' }}</td>
                        <td>
                            @if($d->device_comm_key)
                                <span class="text-muted small"><i class="bi bi-key me-1"></i>••••</span>
                            @else
                                <span class="badge text-bg-warning">No key</span>
                            @endif
                        </td>
                        <td class="small">{{ $d->first_seen }}</td>
                        <td class="small">{{ $d->last_seen }}</td>
                        <td>
                            @include('layouts.partials.status-badge', ['status' => $d->state])
                        </td>
                        <td class="text-end">
                            @if($d->state === 'detected')
                                <div class="table-row-actions justify-content-end">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#assignDevice{{ $d->id }}">
                                        <i class="bi bi-link-45deg"></i> Assign
                                    </button>
                                    <form action="{{ route('devices.ignorePending', $d->id) }}" method="POST"
                                        data-confirm="Ignore this device?">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-slash-circle"></i> Ignore
                                        </button>
                                    </form>
                                </div>
                            @elseif($d->state === 'assigned')
                                <div class="table-row-actions justify-content-end">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#editDevice{{ $d->id }}">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <form action="{{ route('devices.disablePending', $d->id) }}" method="POST"
                                        data-confirm="Disable this device?">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                            <i class="bi bi-stop-circle"></i> Disable
                                        </button>
                                    </form>
                                    <form action="{{ route('devices.unassignPending', $d->id) }}" method="POST"
                                        data-confirm="Unassign this device? It will be removed and returned to detected.">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-unlink"></i> Unassign
                                        </button>
                                    </form>
                                </div>
                            @elseif($d->state === 'blocked')
                                <div class="table-row-actions justify-content-end">
                                    <form action="{{ route('devices.enablePending', $d->id) }}" method="POST"
                                        data-confirm="Enable this device?">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-play-circle"></i> Enable
                                        </button>
                                    </form>
                                    <form action="{{ route('devices.unassignPending', $d->id) }}" method="POST"
                                        data-confirm="Unassign this device? It will be removed and returned to detected.">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-unlink"></i> Unassign
                                        </button>
                                    </form>
                                </div>
                            @elseif($d->state === 'ignored')
                                <div class="table-row-actions justify-content-end">
                                    <form action="{{ route('devices.redetectPending', $d->id) }}" method="POST"
                                        data-confirm="Re-detect this device?">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-search"></i> Re-detect
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="8">
                            @include('layouts.partials.empty-state', [
                                'icon' => 'bi-usb-plug',
                                'title' => 'No pending devices',
                            ])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($log->hasPages())
        <div class="pagination-wrapper">
            {{ $log->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@foreach($log as $d)
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
                            <label class="form-label">Company <span class="required-mark">*</span></label>
                            <select name="company_id" class="form-select" required>
                                <option value="">-- Select Company --</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" @selected(current_company_id() === $company->id)>{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Area</label>
                            <select name="area_id" class="form-select area-select">
                                <option value="">-- None --</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}" data-company="{{ $area->company_id }}" @if(str_starts_with($area->name, 'Default')) data-default="1" @endif>{{ $area->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Comm Key <span class="required-mark">*</span></label>
                            <input type="text" name="comm_key" class="form-control" inputmode="numeric"
                                pattern="[0-9]{4,8}" maxlength="8" placeholder="4-8 digit device communication key" required>
                            <div class="form-text">Must match the communication key configured on the device. Data is only accepted when this key matches.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Assign
                        </button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach

@foreach($log as $d)
    @if($d->state === 'assigned')
        <div class="modal fade" id="editDevice{{ $d->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('devices.assignPending') }}" method="POST">
                        @csrf
                        <input type="hidden" name="sn" value="{{ $d->sn }}">
                        <div class="modal-header">
                            <h5 class="modal-title">Re-assign Device {{ $d->sn }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Company <span class="required-mark">*</span></label>
                                <select name="company_id" class="form-select" required>
                                    <option value="">-- Select Company --</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" @selected($d->device_company_id === $company->id)>{{ $company->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Area</label>
                                <select name="area_id" class="form-select area-select">
                                    <option value="">-- None --</option>
                                    @foreach($areas as $area)
                                        <option value="{{ $area->id }}" data-company="{{ $area->company_id }}" @selected($d->device_area_id === $area->id) @if(str_starts_with($area->name, 'Default')) data-default="1" @endif>{{ $area->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Comm Key <span class="required-mark">*</span></label>
                                <input type="text" name="comm_key" class="form-control" inputmode="numeric"
                                    pattern="[0-9]{4,8}" maxlength="8" placeholder="4-8 digit device communication key"
                                    value="{{ $d->device_comm_key }}" required>
                                <div class="form-text">Must match the communication key configured on the device. Data is only accepted when this key matches.</div>
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
    @endif
@endforeach
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
            modal.addEventListener('show.bs.modal', filterAreas);
        });
    });
</script>
@endsection
