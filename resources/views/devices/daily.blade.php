@extends('layouts.app')

@section('content')
@include('layouts.partials.page-header', [
    'title' => 'Daily Summary',
    'subtitle' => 'Per-employee daily attendance summary with first check-in and last check-out.',
])

<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-funnel me-1"></i>Filters
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label">Date</label>
                <input type="date" id="date" class="form-control">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button id="filter_button" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i>Show Records
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card table-section">
    <div class="card-header">
        <i class="bi bi-table me-1"></i>Daily Attendance
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered" id="attendanceTable">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Employee Name</th>
                    <th>Scheduled In</th>
                    <th>Scheduled Out</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Total Time</th>
                    <th>Status</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const yesterday = new Date(new Date().setDate(new Date().getDate() - 1)).toISOString().split('T')[0];
        document.getElementById('date').max = yesterday;
    });
</script>

<script>
$(document).ready(function () {
    var table = $('#attendanceTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: "{{ route('devices.getDailyAttendanceSummary') }}",
            data: function (d) {
                d.start_date = $('#date').val();
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
            { data: 'employee_id', name: 'employee_id' },
            { data: 'employee_name', name: 'employee_name' },
            { data: 'scheduled_in', name: 'scheduled_in' },
            { data: 'scheduled_out', name: 'scheduled_out' },
            {
                data: 'first_in',
                name: 'first_in',
                render: function (data) {
                    return data === 'No Checkin'
                        ? '<span class="badge text-bg-danger badge-soft"><i class="bi bi-x-circle"></i>No Checkin</span>'
                        : data;
                }
            },
            {
                data: 'last_out',
                name: 'last_out',
                render: function (data) {
                    return data === 'No Checkout'
                        ? '<span class="badge text-bg-warning badge-soft"><i class="bi bi-clock"></i>No Checkout</span>'
                        : data;
                }
            },
            { data: 'total_time', name: 'total_time' },
            {
                data: 'status',
                name: 'status',
                render: function (data) {
                    if (data === 'no schedule') {
                        return '<span class="badge text-bg-secondary badge-soft"><i class="bi bi-calendar-x"></i>No Schedule</span>';
                    }
                    if (data === 'absent') {
                        return '<span class="badge text-bg-danger badge-soft"><i class="bi bi-x-circle"></i>Absent</span>';
                    }
                    if (data === 'late & early leave') {
                        return '<span class="badge text-bg-danger badge-soft"><i class="bi bi-clock-history"></i>Late &amp; Early Leave</span>';
                    }
                    if (data === 'late') {
                        return '<span class="badge text-bg-warning badge-soft"><i class="bi bi-clock"></i>Late</span>';
                    }
                    if (data === 'early leave') {
                        return '<span class="badge text-bg-info badge-soft"><i class="bi bi-clock"></i>Early Leave</span>';
                    }
                    if (data === 'on time') {
                        return '<span class="badge text-bg-success badge-soft"><i class="bi bi-check-circle"></i>On Time</span>';
                    }
                    return data;
                }
            },
        ],
        order: [[0, 'asc']]
    });

    $('#filter_button').click(function () {
        table.draw();
    });
});
</script>
@endsection
