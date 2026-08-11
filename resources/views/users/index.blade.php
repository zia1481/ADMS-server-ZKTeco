@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Users</h2>
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('failed'))
        <div class="alert alert-danger">
            {{ session('failed') }}
        </div>
    @endif
    <hr>

    <form action="{{ route('users.store') }}" method="POST" class="container border rounded p-4 bg-light">
        @csrf
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter Name" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter Email" required>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password"
                        placeholder="Enter Password" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="company_admin">Company Admin</option>
                        @if(!auth()->user()->isSuperAdmin())
                            <option value="viewer">Viewer</option>
                        @endif
                    </select>
                </div>
            </div>
        </div>
        @if(auth()->user()->isSuperAdmin())
            <div class="row mb-3" id="company-row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="company_id" class="form-label">Company</label>
                        <select class="form-control" id="company_id" name="company_id" required>
                            <option value="">-- Select Company --</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" @selected(current_company_id() === $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        @endif
        <div class="d-flex">
            <div class="ms-auto">
                <button type="submit" class="btn btn-primary pull-right">Create</button>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const role = document.getElementById('role');
            const companyRow = document.getElementById('company-row');
            const companySelect = document.getElementById('company_id');

            function toggleCompany() {
                if (companyRow) {
                    companyRow.style.display = role.value === 'super_admin' ? 'none' : '';
                }
                if (companySelect && role.value === 'super_admin') {
                    companySelect.value = '';
                }
            }

            role.addEventListener('change', toggleCompany);
            toggleCompany();
        });
    </script>

    <table class="table table-bordered mt-4">
        <thead>
            <tr>
                <th>Admin Name</th>
                <th>Employee Email</th>
                <th>Role</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $user->role)) }}</td>
                    <td>
                        <form action="{{ route('users.destroy', $user->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
