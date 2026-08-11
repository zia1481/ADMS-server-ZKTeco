@extends('layouts.app')

@section('content')
@include('layouts.partials.page-header', [
    'title' => $lable,
    'subtitle' => 'Raw communication log captured from the attendance devices.',
])

<div class="card table-section">
    <div class="card-header">
        <i class="bi bi-table me-1"></i>Log Entries
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered" id="devices">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Url</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                @forelse($log as $d)
                    <tr>
                        <td>{{ $d->id }}</td>
                        <td><span class="font-monospace small">{{ $d->url }}</span></td>
                        <td class="text-break small">{{ $d->data }}</td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="3">
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
