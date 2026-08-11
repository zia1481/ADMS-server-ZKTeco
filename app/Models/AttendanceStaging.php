<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceStaging extends Model
{
    use HasFactory;

    public const STATE_PENDING = 'pending';
    public const STATE_PROCESSED = 'processed';
    public const STATE_DUPLICATE = 'duplicate';
    public const STATE_REJECTED = 'rejected';

    protected $table = 'attendance_staging';

    protected $fillable = [
        'sn',
        'device_id',
        'company_id',
        'table',
        'stamp',
        'payload',
        'employee_id',
        'punch_time',
        'status1',
        'status2',
        'status3',
        'status4',
        'status5',
        'state',
        'error',
    ];
}
