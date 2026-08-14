<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EmployeeImportExportTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Corp',
            'code' => 'TEST'.mt_rand(1000, 9999),
        ]);

        $this->department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Engineering',
            'code' => 'ENG',
        ]);
    }

    private function superAdmin(): User
    {
        return User::create([
            'name' => 'Super Admin',
            'email' => 'super.'.mt_rand(1000, 9999).'@example.com',
            'password' => 'password',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    private function companyAdmin(): User
    {
        return User::create([
            'name' => 'Company Admin',
            'email' => 'admin.'.mt_rand(1000, 9999).'@example.com',
            'password' => 'password',
            'role' => User::ROLE_COMPANY_ADMIN,
            'company_id' => $this->company->id,
        ]);
    }

    private function csvFile(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('employees.csv', $contents);
    }

    public function test_super_admin_can_access_employees_page(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('employees.index'))
            ->assertOk();
    }

    public function test_company_admin_cannot_access_import_endpoint(): void
    {
        $this->actingAs($this->companyAdmin())
            ->post(route('employees.import.test'))
            ->assertForbidden();
    }

    public function test_import_test_flags_required_duplicate_and_unknown_rows(): void
    {
        Employee::create([
            'company_id' => $this->company->id,
            'employee_id' => 1001,
            'name' => 'Existing Employee',
        ]);

        $csv = implode("\n", [
            'employee_id,name,department,position,phone,email',
            '1002,Alice Smith,Engineering,Engineer,123,alice@example.com',
            ',,,', // missing employee_id + name
            '1003,,Engineering,Engineer,123,bad-email',
            '1004,Duplicate Id,Engineering,,,',
            '1004,Duplicate Id Again,Engineering,,,',
            '1001,Existing Id,Engineering,,,',
            '1005,Unknown Dept,Marketing,,,',
            'abc,Not Numeric,Engineering,,,',
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->postJson(route('employees.import.test'), [
                'company_id' => $this->company->id,
                'file' => $this->csvFile($csv),
            ])
            ->assertOk();

        $json = $response->json();

        $this->assertTrue($json['ok']);
        $this->assertSame(8, $json['total']);
        $this->assertSame(2, $json['valid']);
        $this->assertSame(6, $json['invalid']);

        $invalidByRow = collect($json['rows'])->where('status', 'invalid')->keyBy('row');

        $this->assertStringContainsString('required', implode(' ', $invalidByRow[3]['errors']));
        $this->assertStringContainsString('not a valid email', implode(' ', $invalidByRow[4]['errors']));
        $this->assertStringContainsString('duplicated', implode(' ', $invalidByRow[6]['errors']));
        $this->assertStringContainsString('already exists', implode(' ', $invalidByRow[7]['errors']));
        $this->assertStringContainsString('not found', implode(' ', $invalidByRow[8]['errors']));
        $this->assertStringContainsString('positive integer', implode(' ', $invalidByRow[9]['errors']));

        $this->assertSame(1, Employee::where('company_id', $this->company->id)->count());
    }

    public function test_import_inserts_only_valid_rows(): void
    {
        Employee::create([
            'company_id' => $this->company->id,
            'employee_id' => 1001,
            'name' => 'Existing Employee',
        ]);

        $csv = implode("\n", [
            'employee_id,name,department,position,phone,email',
            '1002,Alice Smith,Engineering,Engineer,123,alice@example.com',
            '1001,Existing Id,Engineering,,,',
            '1003,Bob Jones,,Designer,456,bob@example.com',
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->postJson(route('employees.import'), [
                'company_id' => $this->company->id,
                'file' => $this->csvFile($csv),
            ])
            ->assertOk();

        $this->assertSame(2, $response->json('imported'));
        $this->assertSame(1, $response->json('invalid'));
        $this->assertSame(3, Employee::where('company_id', $this->company->id)->count());

        $this->assertNotNull(
            Employee::where('company_id', $this->company->id)->where('employee_id', 1002)->first()
        );
        $this->assertNotNull(
            Employee::where('company_id', $this->company->id)->where('employee_id', 1003)->first()
        );
    }

    public function test_import_attaches_department_by_name(): void
    {
        $csv = implode("\n", [
            'employee_id,name,department',
            '2001,Alice Smith,Engineering',
        ]);

        $this->actingAs($this->superAdmin())
            ->postJson(route('employees.import'), [
                'company_id' => $this->company->id,
                'file' => $this->csvFile($csv),
            ])
            ->assertOk();

        $employee = Employee::where('company_id', $this->company->id)
            ->where('employee_id', 2001)
            ->first();

        $this->assertNotNull($employee);
        $this->assertSame($this->department->id, $employee->department_id);
    }

    public function test_super_admin_dashboard_renders_import_export_ui(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dashboard.super-admin'))
            ->assertOk()
            ->assertSee('Import Employees')
            ->assertSee('Export CSV')
            ->assertSee('Export Excel');
    }

    public function test_export_returns_csv_download(): void
    {
        Employee::create([
            'company_id' => $this->company->id,
            'employee_id' => 1001,
            'name' => 'Alice Smith',
            'department_id' => $this->department->id,
            'position' => 'Engineer',
            'phone' => '123',
            'email' => 'alice@example.com',
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->get(route('employees.export', [
                'company_id' => $this->company->id,
                'format' => 'csv',
            ]))
            ->assertOk();

        $disposition = $response->headers->get('content-disposition', '');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('.csv', $disposition);
    }
}
