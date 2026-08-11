@extends('layouts.app')

@section('content')
@include('layouts.partials.page-header', [
    'title' => 'Attendance Records',
    'subtitle' => 'View raw attendance transactions captured from devices.',
])

<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-funnel me-1"></i>Filters
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" id="start_date" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" id="end_date" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Employee ID</label>
                <input type="text" id="employee_id_search" class="form-control" placeholder="Search Employee ID">
            </div>
            <div class="col-md-3">
                <label class="form-label">Employee Name</label>
                <input type="text" id="employee_name_search" class="form-control" placeholder="Search Employee Name">
            </div>
        </div>
        <div class="d-flex justify-content-end mt-3">
            <button id="filter_button" class="btn btn-primary">
                <i class="bi bi-funnel me-1"></i>Filter
            </button>
        </div>
    </div>
</div>

<div class="card table-section">
    <div class="card-header">
        <i class="bi bi-table me-1"></i>Transactions
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered" id="attendanceTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>SN</th>
                    <th>Employee ID</th>
                    <th>Employee Name</th>
                    <th>Timestamp</th>
                    <th>Status</th>
                    <th>Type</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const today = new Date().toISOString().split('T')[0];
        const yesterday = new Date(new Date().setDate(new Date().getDate() - 1)).toISOString().split('T')[0];

        document.getElementById('start_date').value = yesterday;
        document.getElementById('end_date').value = today;
    });
</script>

<script>
$(document).ready(function () {
    var table = $('#attendanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('devices.getAttendance') }}",
            data: function (d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.employee_id = $('#employee_id_search').val();
                d.employee_name = $('#employee_name_search').val();
            }
        },
        dom: '<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6 text-md-end"B>>t<"row mt-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 text-md-end"p>>',
        buttons: [
            'excel', 'pdf', 'print', 'pageLength'
        ],
        searching: false,
        pageLength: 100,
        lengthMenu: [50, 100, 500, { label: 'All', value: -1 }],
        columns: [
            { data: 'id', name: 'id' },
            { data: 'sn', name: 'sn' },
            { data: 'employee_id', name: 'employee_id' },
            { data: 'employee_name', name: 'employee_name' },
            {
                data: 'timestamp',
                name: 'timestamp',
                render: function (data, type, row) {
                    return new Date(data)
                        .toLocaleString('en-US',
                            {
                                year: 'numeric',
                                month: 'numeric',
                                day: 'numeric',
                                hour: 'numeric',
                                minute: 'numeric',
                                hour12: true
                            });
                }
            },
            {
                data: 'status1',
                name: 'status1',
                render: function (data, type, row) {
                    var label = data == 1 ? 'out' : 'in';
                    return '<span class="badge text-bg-' + (data == 1 ? 'secondary' : 'info') + ' badge-soft">' +
                        '<i class="bi ' + (data == 1 ? 'bi-box-arrow-right' : 'bi-box-arrow-in-right') + '"></i>' +
                        (data == 1 ? 'Out' : 'In') + '</span>';
                }
            },
            {
                data: 'status2',
                name: 'status2',
                render: function (data, type, row) {
                    if (data == 15) return '<span class="badge text-bg-light border">Face</span>';
                    if (data == 25) return '<span class="badge text-bg-light border">Palm</span>';
                    return data;
                }
            },
        ],
        order: [[0, 'desc']]
    });

    $('#filter_button').click(function () {
        table.draw();
    });

    $('#employee_id_search, #employee_name_search').on('keyup', function () {
        table.draw();
    });
});
</script>
@endsection
