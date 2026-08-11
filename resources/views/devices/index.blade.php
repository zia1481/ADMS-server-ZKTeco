@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>{{ $lable }}</h2>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <hr>

        <form method="GET" action="{{ route('devices.index') }}" class="row mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search Serial / Name" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="registered" @selected(request('status') === 'registered')>Registered</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="blocked" @selected(request('status') === 'blocked')>Blocked</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
            <div class="col-md-2 text-end">
                <a href="{{ route('devices.pending') }}" class="btn btn-warning">New Devices</a>
            </div>
        </form>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Serial Number</th>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Area</th>
                    <th>IP Address</th>
                    <th>Model</th>
                    <th>Status</th>
                    <th>Online</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($log as $d)
                    <tr>
                        <td>{{ $d->no_sn }}</td>
                        <td>{{ $d->nama }}</td>
                        <td>{{ $d->company_name ?? '-' }}</td>
                        <td>{{ $d->area_name ?? '-' }}</td>
                        <td>{{ $d->ip_address ?? '-' }}</td>
                        <td>{{ $d->model ?? '-' }}</td>
                        <td>
                            @if($d->status === 'registered')
                                <span class="badge bg-success">Registered</span>
                            @elseif($d->status === 'blocked')
                                <span class="badge bg-danger">Blocked</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </td>
                        <td>{{ $d->online }}</td>
                        <td class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                data-bs-target="#editDevice{{ $d->id }}">Edit</button>
                        </td>
                    </tr>

                    <div class="modal fade" id="editDevice{{ $d->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('devices.update', $d->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Device {{ $d->no_sn }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" name="nama" class="form-control" value="{{ $d->nama }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Area</label>
                                            <select name="area_id" class="form-control">
                                                <option value="">-- None --</option>
                                                @foreach($areas as $area)
                                                    <option value="{{ $area->id }}" @selected($area->id === $d->area_id)>{{ $area->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-control">
                                                <option value="registered" @selected($d->status === 'registered')>Registered</option>
                                                <option value="pending" @selected($d->status === 'pending')>Pending</option>
                                                <option value="blocked" @selected($d->status === 'blocked')>Blocked</option>
                                            </select>
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
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No devices found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">
            {{ $log->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
