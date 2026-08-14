<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private Builder|Relation $query) {}

    public function query(): Builder|Relation
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['Employee ID', 'Name', 'Department', 'Position', 'Phone', 'Email'];
    }

    public function map($employee): array
    {
        return [
            $employee->employee_id,
            $employee->name,
            $employee->department->name ?? '',
            $employee->position ?? '',
            $employee->phone ?? '',
            $employee->email ?? '',
        ];
    }
}
