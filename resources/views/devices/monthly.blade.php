@extends('layouts.app')

@section('content')
@include('layouts.partials.page-header', [
    'title' => 'Monthly Attendance',
    'subtitle' => 'Monthly attendance summary for a specific employee.',
])

<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-funnel me-1"></i>Filters
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label">Start Date <span class="required-mark">*</span></label>
                <input type="date" id="start_date" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date <span class="required-mark">*</span></label>
                <input type="date" id="end_date" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Employee ID</label>
                <input type="text" id="employee_id" class="form-control" placeholder="Employee ID">
            </div>
            <div class="col-md-3">
                <label class="form-label">Employee Name</label>
                <input type="text" id="employee_name" class="form-control" placeholder="Employee Name">
            </div>
        </div>
        <div class="d-flex justify-content-end mt-3">
            <button id="filter_button" class="btn btn-primary">
                <i class="bi bi-search me-1"></i>Show Records
            </button>
        </div>
    </div>
</div>

<div class="card table-section">
    <div class="card-header">
        <i class="bi bi-table me-1"></i>Monthly Attendance
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-bordered" id="attendanceTable">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Employee Name</th>
                    <th>Date</th>
                    <th>Scheduled In</th>
                    <th>Scheduled Out</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Total Hours</th>
                    <th>Status</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {
    var table = $('#attendanceTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: "{{ route('devices.getMonthlyAttendanceSummary') }}",
            data: function (d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.employee_id = $('#employee_id').val();
                d.employee_name = $('#employee_name').val();
            }
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip<"row mt-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6 text-md-end"B>>',
        buttons: [
            'excel', 'pdf', 'csv', 'print'
        ],
        columns: [
            { data: 'employee_id', name: 'employee_id' },
            { data: 'employee_name', name: 'employee_name' },
            { data: 'date', name: 'date' },
            { data: 'scheduled_in', name: 'scheduled_in' },
            { data: 'scheduled_out', name: 'scheduled_out' },
            { data: 'first_in', name: 'first_in' },
            { data: 'last_out', name: 'last_out' },
            { data: 'total_hours', name: 'total_hours' },
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
        order: [[2, 'asc']]
    });

    $('#filter_button').click(function () {
        if (!$('#start_date').val() || !$('#end_date').val() || (!$('#employee_id').val() && !$('#employee_name').val())) {
            alert('Please enter start date, end date, and either employee ID or name.');
            return;
        }
        table.ajax.reload();
    });

    // Set default dates
    var today = new Date();
    var firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    $('#start_date').val(firstDayOfMonth.toISOString().split('T')[0]);
    $('#end_date').val(today.toISOString().split('T')[0]);
});
</script>
@endsection
