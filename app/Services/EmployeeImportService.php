<?php

namespace App\Services;

use App\Imports\EmployeeImport;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeImportService
{
    private const HEADER_MAP = [
        'employeeid' => 'employee_id',
        'employeecode' => 'employee_id',
        'badgenumber' => 'employee_id',
        'name' => 'name',
        'fullname' => 'name',
        'employeename' => 'name',
        'department' => 'department',
        'dept' => 'department',
        'section' => 'department',
        'position' => 'position',
        'designation' => 'position',
        'jobtitle' => 'position',
        'phone' => 'phone',
        'mobile' => 'phone',
        'contact' => 'phone',
        'email' => 'email',
    ];

    /**
     * Parse an uploaded CSV/XLSX file into an array of raw rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parse(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'csv' || $extension === 'txt') {
            return $this->parseCsv($file);
        }

        return $this->parseXlsx($file);
    }

    /**
     * Validate every row and build an import report.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function validate(array $rows, int $companyId): array
    {
        $departments = Department::forCompany($companyId)->get(['id', 'name', 'code']);
        $existingIds = Employee::forCompany($companyId)->pluck('employee_id')->flip();

        $seen = [];
        $reportRows = [];
        $valid = 0;
        $invalid = 0;

        foreach ($rows as $index => $rawRow) {
            $rowNo = $index + 2;
            $row = $this->normalizeRow($rawRow);

            $employeeId = $row['employee_id'] ?? null;
            $name = $row['name'] ?? null;
            $department = $row['department'] ?? null;
            $email = $row['email'] ?? null;
            $phone = $row['phone'] ?? null;
            $position = $row['position'] ?? null;

            $errors = [];
            $normalizedId = null;

            if ($employeeId === null || (string) $employeeId === '') {
                $errors[] = 'Employee ID is required.';
            } elseif (! is_numeric($employeeId) || (int) $employeeId != $employeeId || (int) $employeeId <= 0) {
                $errors[] = 'Employee ID must be a positive integer.';
            } else {
                $normalizedId = (int) $employeeId;

                if (isset($seen[$normalizedId])) {
                    $errors[] = "Employee ID {$normalizedId} is duplicated in the file (first seen on row {$seen[$normalizedId]}).";
                }
                $seen[$normalizedId] = $rowNo;

                if (! $errors && isset($existingIds[$normalizedId])) {
                    $errors[] = "Employee ID {$normalizedId} already exists for this company.";
                }
            }

            $cleanName = $name === null ? '' : trim((string) $name);
            if ($cleanName === '') {
                $errors[] = 'Name is required.';
            } elseif (mb_strlen($cleanName) > 255) {
                $errors[] = 'Name must not exceed 255 characters.';
            }

            $departmentId = null;
            $cleanDepartment = $department === null ? '' : trim((string) $department);
            if ($cleanDepartment !== '') {
                $departmentId = $this->resolveDepartmentId($cleanDepartment, $departments);
                if ($departmentId === null) {
                    $errors[] = "Department \"{$cleanDepartment}\" was not found for this company.";
                }
            }

            $cleanEmail = $email === null ? '' : trim((string) $email);
            if ($cleanEmail !== '') {
                if (! filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Email \"{$cleanEmail}\" is not a valid email address.";
                } elseif (mb_strlen($cleanEmail) > 255) {
                    $errors[] = 'Email must not exceed 255 characters.';
                }
            }

            $cleanPhone = $phone === null ? '' : trim((string) $phone);
            if (mb_strlen($cleanPhone) > 50) {
                $errors[] = 'Phone must not exceed 50 characters.';
            }

            $cleanPosition = $position === null ? '' : trim((string) $position);
            if (mb_strlen($cleanPosition) > 255) {
                $errors[] = 'Position must not exceed 255 characters.';
            }

            if ($errors) {
                $invalid++;
            } else {
                $valid++;
            }

            $reportRows[] = [
                'row' => $rowNo,
                'employee_id' => $normalizedId ?? $employeeId,
                'name' => $cleanName,
                'status' => $errors ? 'invalid' : 'valid',
                'errors' => $errors,
                'data' => $errors ? null : [
                    'company_id' => $companyId,
                    'employee_id' => $normalizedId,
                    'name' => $cleanName,
                    'department_id' => $departmentId,
                    'position' => $cleanPosition !== '' ? $cleanPosition : null,
                    'phone' => $cleanPhone !== '' ? $cleanPhone : null,
                    'email' => $cleanEmail !== '' ? $cleanEmail : null,
                ],
            ];
        }

        return [
            'total' => count($rows),
            'valid' => $valid,
            'invalid' => $invalid,
            'rows' => $reportRows,
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseCsv(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open the uploaded CSV file.');
        }

        $delimiter = $this->detectDelimiter($path);
        $headers = null;
        $rows = [];

        $firstChunk = fread($handle, 3);
        if ($firstChunk !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($line === [null] || (count($line) === 1 && trim((string) $line[0]) === '')) {
                continue;
            }

            if ($headers === null) {
                $headers = array_map(fn ($header) => trim((string) $header), $line);

                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $line[$index] ?? '';
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseXlsx(UploadedFile $file): array
    {
        $sheets = Excel::toArray(new EmployeeImport, $file);

        return $sheets[0] ?? [];
    }

    private function detectDelimiter(string $path): string
    {
        $candidates = [',', ';', "\t"];
        $scores = [];

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return ',';
        }

        for ($i = 0; $i < 5; $i++) {
            $line = fgets($handle);
            if ($line === false) {
                break;
            }
            foreach ($candidates as $delimiter) {
                $scores[$delimiter] = ($scores[$delimiter] ?? 0) + substr_count($line, $delimiter);
            }
        }
        fclose($handle);

        arsort($scores);
        $best = key($scores);

        return $best !== null && $scores[$best] > 0 ? $best : ',';
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeRow(array $raw): array
    {
        $row = [];
        foreach ($raw as $key => $value) {
            $normalized = preg_replace('/[^a-z]/i', '', strtolower((string) $key)) ?? '';
            $canonical = self::HEADER_MAP[$normalized] ?? null;
            if ($canonical !== null) {
                $row[$canonical] = is_string($value) ? trim($value) : $value;
            }
        }

        return $row;
    }

    /**
     * @param  Collection<int, Department>  $departments
     */
    private function resolveDepartmentId(string $value, Collection $departments): ?int
    {
        $needle = mb_strtolower(trim($value));

        foreach ($departments as $department) {
            if (mb_strtolower((string) $department->name) === $needle) {
                return (int) $department->id;
            }
        }

        foreach ($departments as $department) {
            if ($department->code !== null && mb_strtolower((string) $department->code) === $needle) {
                return (int) $department->id;
            }
        }

        return null;
    }
}
