@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Super Admin Dashboard</h2>
    <hr>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-header">Companies</div>
                <div class="card-body">
                    <h5 class="card-title">{{ $totalCompanies }}</h5>
                    <p class="card-text">Total companies registered.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3">
                <div class="card-header">New Devices</div>
                <div class="card-body">
                    <h5 class="card-title">{{ $totalPendingDevices }}</h5>
                    <p class="card-text">Devices waiting to be assigned.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">Company Admins</div>
                <div class="card-body">
                    <h5 class="card-title">{{ $totalCompanyAdmins }}</h5>
                    <p class="card-text">Company admin users created.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Companies</h5>
                    <p class="card-text">Create, edit or delete companies. A default Area is created automatically for each new company.</p>
                    <a href="{{ route('companies.index') }}" class="btn btn-primary">Manage Companies</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">New Devices</h5>
                    <p class="card-text">Assign devices that contacted the server to a company and its default area.</p>
                    <a href="{{ route('devices.pending') }}" class="btn btn-warning">Assign New Devices</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Company Admins</h5>
                    <p class="card-text">Create company admin users. They manage their company's attendance from the Company Dashboard.</p>
                    <a href="{{ route('users.index') }}" class="btn btn-success">Create Company Admin</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
