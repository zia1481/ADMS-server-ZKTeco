@extends('layouts.app')

@section('content')
@include('layouts.partials.page-header', [
    'title' => 'Super Admin Dashboard',
    'subtitle' => 'Overview of companies, devices and administrators.',
])

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-primary"><i class="bi bi-buildings"></i></div>
            <div>
                <div class="stat-label">Companies</div>
                <div class="stat-value">{{ $totalCompanies }}</div>
                <div class="stat-sub">Registered companies</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-warning"><i class="bi bi-usb-plug"></i></div>
            <div>
                <div class="stat-label">New Devices</div>
                <div class="stat-value">{{ $totalPendingDevices }}</div>
                <div class="stat-sub">Awaiting assignment</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-success"><i class="bi bi-person-gear"></i></div>
            <div>
                <div class="stat-label">Company Admins</div>
                <div class="stat-value">{{ $totalCompanyAdmins }}</div>
                <div class="stat-sub">Admin users</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-info"><i class="bi bi-hdd-network"></i></div>
            <div>
                <div class="stat-label">Devices</div>
                <div class="stat-value">{{ $totalDevices }}</div>
                <div class="stat-sub">Registered devices</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-secondary"><i class="bi bi-geo-alt"></i></div>
            <div>
                <div class="stat-label">Areas</div>
                <div class="stat-value">{{ $totalAreas }}</div>
                <div class="stat-sub">Total areas</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-primary"><i class="bi bi-diagram-3"></i></div>
            <div>
                <div class="stat-label">Departments</div>
                <div class="stat-value">{{ $totalDepartments }}</div>
                <div class="stat-sub">Total sections</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-success"><i class="bi bi-people"></i></div>
            <div>
                <div class="stat-label">Employees</div>
                <div class="stat-value">{{ $totalEmployees }}</div>
                <div class="stat-sub">Total employees</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-info"><i class="bi bi-clock-history"></i></div>
            <div>
                <div class="stat-label">Shifts</div>
                <div class="stat-value">{{ $totalShifts }}</div>
                <div class="stat-sub">Total shifts</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-lightning-charge me-1"></i>Quick Actions</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title fs-6 fw-semibold"><i class="bi bi-buildings text-primary me-2"></i>Companies</h5>
                        <p class="card-text small text-muted mb-3">Create, edit or delete companies. A default Area is created automatically for each new company.</p>
                        <a href="{{ route('companies.index') }}" class="btn btn-sm btn-primary">Manage Companies</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title fs-6 fw-semibold"><i class="bi bi-usb-plug text-warning me-2"></i>New Devices</h5>
                        <p class="card-text small text-muted mb-3">Assign devices that contacted the server to a company and its default area.</p>
                        <a href="{{ route('devices.pending') }}" class="btn btn-sm btn-warning">Assign New Devices</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title fs-6 fw-semibold"><i class="bi bi-person-gear text-success me-2"></i>Company Admins</h5>
                        <p class="card-text small text-muted mb-3">Create company admin users. They manage their company's attendance from the Company Dashboard.</p>
                        <a href="{{ route('users.index') }}" class="btn btn-sm btn-success">Create Company Admin</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title fs-6 fw-semibold"><i class="bi bi-receipt text-info me-2"></i>Device Logs</h5>
                        <p class="card-text small text-muted mb-3">Raw communication log captured from the attendance devices.</p>
                        <a href="{{ route('devices.DeviceLog') }}" class="btn btn-sm btn-info">View Device Logs</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title fs-6 fw-semibold"><i class="bi bi-fingerprint text-info me-2"></i>Finger Logs</h5>
                        <p class="card-text small text-muted mb-3">Raw fingerprint communication log captured from the attendance devices.</p>
                        <a href="{{ route('devices.FingerLog') }}" class="btn btn-sm btn-info">View Finger Logs</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title fs-6 fw-semibold"><i class="bi bi-people text-success me-2"></i>Employees</h5>
                        <p class="card-text small text-muted mb-3">Bulk import employees for a company, or export the current roster to CSV / Excel.</p>
                        <select class="form-select form-select-sm mb-2" id="importExportCompany">
                            <option value="">-- Select Company --</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                data-bs-target="#importEmployeesModal" id="importEmployeesBtn" disabled>
                                <i class="bi bi-file-earmark-arrow-up me-1"></i>Import Employees
                            </button>
                            <div class="btn-group">
                                <a href="#" class="btn btn-sm btn-outline-success" id="exportCsvBtn" disabled>
                                    <i class="bi bi-filetype-csv me-1"></i>Export CSV
                                </a>
                                <a href="#" class="btn btn-sm btn-outline-success" id="exportXlsxBtn" disabled>
                                    <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
                                </a>
                                <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-person-lines-fill me-1"></i>Manage
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importEmployeesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-1"></i>Import Employees</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Company</label>
                    <div id="importCompanyLabel" class="fw-semibold text-primary">--</div>
                </div>
                <div class="mb-3">
                    <label for="importFile" class="form-label">File (CSV or Excel)</label>
                    <input type="file" class="form-control" id="importFile" accept=".csv,.xlsx" required>
                    <div class="help-text">Headers: employee_id, name, department, position, phone, email. Only the first sheet is read for Excel files.</div>
                </div>
                <div class="alert alert-info py-2 mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Rows are validated before any data is written. Click <strong>Test File</strong> first to check required fields,
                    duplicate employee IDs, unknown departments and invalid emails. Rows that already exist for the selected company are skipped.
                </div>
                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-outline-primary" id="testImportBtn">
                        <i class="bi bi-clipboard-check me-1"></i>Test File
                    </button>
                    <button type="button" class="btn btn-primary" id="runImportBtn" disabled>
                        <i class="bi bi-check-lg me-1"></i>Import Valid Rows
                    </button>
                    <span class="spinner-border spinner-border-sm ms-auto d-none" id="importSpinner" role="status"></span>
                </div>
                <div id="importResult"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var csrf = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrf ? csrf.getAttribute('content') : '';
        var companySelect = document.getElementById('importExportCompany');
        var importBtn = document.getElementById('importEmployeesBtn');
        var exportCsvBtn = document.getElementById('exportCsvBtn');
        var exportXlsxBtn = document.getElementById('exportXlsxBtn');
        var importCompanyLabel = document.getElementById('importCompanyLabel');
        var importFile = document.getElementById('importFile');
        var testBtn = document.getElementById('testImportBtn');
        var runBtn = document.getElementById('runImportBtn');
        var spinner = document.getElementById('importSpinner');
        var resultBox = document.getElementById('importResult');

        function selectedCompany() {
            return companySelect.value;
        }

        function selectedCompanyName() {
            var opt = companySelect.options[companySelect.selectedIndex];
            return opt ? opt.text : '--';
        }

        function updateControls() {
            var hasCompany = selectedCompany() !== '';
            importBtn.disabled = !hasCompany;
            exportCsvBtn.disabled = !hasCompany;
            exportXlsxBtn.disabled = !hasCompany;
            if (hasCompany) {
                exportCsvBtn.href = "{{ route('employees.export') }}?company_id=" + selectedCompany() + "&format=csv";
                exportXlsxBtn.href = "{{ route('employees.export') }}?company_id=" + selectedCompany() + "&format=xlsx";
            }
            if (importCompanyLabel) {
                importCompanyLabel.textContent = hasCompany ? selectedCompanyName() : '--';
            }
        }

        companySelect.addEventListener('change', function () {
            importFile.value = '';
            runBtn.disabled = true;
            resultBox.innerHTML = '';
            updateControls();
        });

        updateControls();

        function postForm(url) {
            var formData = new FormData();
            formData.append('company_id', selectedCompany());
            formData.append('file', importFile.files[0]);

            return fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: formData
            }).then(function (response) {
                return response.json().catch(function () {
                    return { ok: false, message: 'Unexpected server response.' };
                }).then(function (data) {
                    data.httpStatus = response.status;
                    return data;
                });
            });
        }

        function renderReport(data) {
            var html = '';
            if (!data.ok) {
                html += '<div class="alert alert-danger py-2"><i class="bi bi-x-octagon me-1"></i>' +
                    escapeHtml(data.message || 'Validation failed.') + '</div>';
                resultBox.innerHTML = html;
                return;
            }

            html += '<div class="card mb-3"><div class="card-body py-2">' +
                '<span class="badge text-bg-secondary">Total: ' + data.total + '</span> ' +
                '<span class="badge text-bg-success">Valid: ' + data.valid + '</span> ' +
                '<span class="badge text-bg-danger">Invalid: ' + data.invalid + '</span>' +
                '</div></div>';

            var errors = (data.rows || []).filter(function (row) { return row.status === 'invalid'; });

            if (errors.length > 0) {
                html += '<div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0">' +
                    '<thead><tr><th>Row</th><th>Employee ID</th><th>Name</th><th>Errors</th></tr></thead><tbody>';
                errors.forEach(function (row) {
                    html += '<tr><td>' + row.row + '</td><td>' + escapeHtml(row.employee_id) + '</td>' +
                        '<td>' + escapeHtml(row.name) + '</td><td><ul class="mb-0 ps-3 small">' +
                        (row.errors || []).map(function (e) { return '<li>' + escapeHtml(e) + '</li>'; }).join('') +
                        '</ul></td></tr>';
                });
                html += '</tbody></table></div>';
            } else {
                html += '<div class="alert alert-success py-2 mb-0"><i class="bi bi-check-circle me-1"></i>' +
                    'All ' + data.total + ' row(s) are valid and ready to import.</div>';
            }

            resultBox.innerHTML = html;
        }

        function escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        testBtn.addEventListener('click', function () {
            if (!selectedCompany()) { alert('Please select a company first.'); return; }
            if (!importFile.files[0]) { alert('Please choose a CSV or Excel file first.'); return; }

            testBtn.disabled = true;
            spinner.classList.remove('d-none');
            resultBox.innerHTML = '';

            postForm("{{ route('employees.import.test') }}").then(function (data) {
                renderReport(data);
                if (data.ok && data.valid > 0) {
                    runBtn.disabled = false;
                } else {
                    runBtn.disabled = true;
                }
            }).finally(function () {
                testBtn.disabled = false;
                spinner.classList.add('d-none');
            });
        });

        runBtn.addEventListener('click', function () {
            if (!selectedCompany() || !importFile.files[0]) return;

            runBtn.disabled = true;
            testBtn.disabled = true;
            spinner.classList.remove('d-none');

            postForm("{{ route('employees.import') }}").then(function (data) {
                renderReport(data);
                if (data.ok) {
                    var msg = data.invalid > 0
                        ? 'Import finished: ' + data.imported + ' row(s) imported, ' + data.invalid + ' row(s) skipped.'
                        : 'Import finished: ' + data.imported + ' row(s) imported.';
                    var flash = document.createElement('div');
                    flash.className = 'alert alert-' + (data.invalid > 0 ? 'warning' : 'success') + ' alert-dismissible fade show py-2';
                    flash.innerHTML = '<i class="bi bi-check-circle me-1"></i>' + msg +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                    var container = document.querySelector('.app-content .container-fluid');
                    if (container) container.prepend(flash);
                    window.location.reload();
                }
            }).finally(function () {
                runBtn.disabled = true;
                testBtn.disabled = false;
                spinner.classList.add('d-none');
            });
        });

        var modalEl = document.getElementById('importEmployeesModal');
        modalEl.addEventListener('hidden.bs.modal', function () {
            importFile.value = '';
            runBtn.disabled = true;
            resultBox.innerHTML = '';
        });
    });
</script>
@endsection
