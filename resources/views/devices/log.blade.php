@extends('layouts.app')

@section('content')
@include('layouts.partials.page-header', [
    'title' => $lable,
    'subtitle' => 'Raw communication log captured from the attendance devices.',
])

<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-funnel me-1"></i>Filter
    </div>
    <div class="card-body">
        <form method="GET" action="{{ $lable === 'Devices Log' ? route('devices.DeviceLog') : route('devices.FingerLog') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="sn" value="{{ $filter_sn ?? '' }}" class="form-control" placeholder="Serial Number (SN)">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                @if(($filter_sn ?? ''))
                    <a href="{{ $lable === 'Devices Log' ? route('devices.DeviceLog') : route('devices.FingerLog') }}" class="btn btn-light">
                        <i class="bi bi-x-lg me-1"></i>Clear
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card table-section">
    <div class="card-header">
        <i class="bi bi-table me-1"></i>Log Entries
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered" id="devices">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>SN</th>
                    <th>Option</th>
                    <th>Created At</th>
                    <th>Data</th>
                    <th>Url</th>
                </tr>
            </thead>
            <tbody>
                @forelse($log as $d)
                    <tr>
                        <td>{{ $d->id }}</td>
                        <td><span class="badge text-bg-light border font-monospace small">{{ $d->sn ?? '-' }}</span></td>
                        <td><span class="font-monospace small">{{ $d->option ?? '-' }}</span></td>
                        <td class="small">{{ $d->created_at ?? '-' }}</td>
                        <td class="small"><span class="text-break" title="{{ $d->data }}">{{ str($d->data)->limit(120) }}</span></td>
                        <td><span class="font-monospace small text-break">{{ $d->url }}</span></td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="6">
                            @include('layouts.partials.empty-state', [
                                'icon' => 'bi-receipt',
                                'title' => 'No log entries found',
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
@endsection